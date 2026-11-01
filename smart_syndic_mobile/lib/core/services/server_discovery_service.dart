import 'dart:async';
import 'dart:convert';
import 'dart:io';
import 'package:flutter/foundation.dart';
import 'package:http/http.dart' as http;

class ServerDiscoveryService {
  static final ServerDiscoveryService _instance = ServerDiscoveryService._internal();
  factory ServerDiscoveryService() => _instance;
  ServerDiscoveryService._internal();

  /// Verify if a candidate URL is running a valid Smart Syndic backend
  Future<bool> verifyServer(String baseUrl, {Duration timeout = const Duration(milliseconds: 1500)}) async {
    try {
      final cleanUrl = baseUrl.endsWith('/') ? baseUrl.substring(0, baseUrl.length - 1) : baseUrl;
      final pingUrl = Uri.parse('$cleanUrl/ping');
      final res = await http.get(pingUrl).timeout(timeout);
      if (res.statusCode == 200) {
        final data = jsonDecode(res.body);
        if (data is Map && data['app'] == 'smart_syndic') {
          return true;
        }
      }
    } catch (_) {}
    return false;
  }

  /// Discover Smart Syndic server in the local network automatically:
  /// 1. Tries the PC hostname (sosta-04.local:8000)
  /// 2. If emulator, tries 10.0.2.2:8000
  /// 3. Scans the local Wi-Fi subnet (e.g. 192.168.x.1..254) for open port 8000
  Future<String?> discoverServer({Function(String status)? onProgress}) async {
    if (kIsWeb) {
      return 'http://127.0.0.1:8000/api/v1';
    }

    onProgress?.call('جاري التحقق عبر اسم الجهاز (sosta-04.local)...');

    // 1. Try hostname
    const hostnameUrl = 'http://sosta-04.local:8000/api/v1';
    if (await verifyServer(hostnameUrl, timeout: const Duration(seconds: 2))) {
      return hostnameUrl;
    }

    // 2. Try Android emulator default if on emulator
    const emulatorUrl = 'http://10.0.2.2:8000/api/v1';
    if (await verifyServer(emulatorUrl, timeout: const Duration(milliseconds: 800))) {
      return emulatorUrl;
    }

    // 3. Scan Local Subnet
    onProgress?.call('جاري فحص شبكة الـ Wi-Fi تلقائياً...');
    final subnet = await _findLocalSubnet();
    if (subnet == null) {
      return null;
    }

    // Concurrently test all IPs in batches of 25 to find open port 8000
    final foundIp = await _scanSubnetForPort8000(subnet, onProgress: onProgress);
    if (foundIp != null) {
      final candidateUrl = 'http://$foundIp:8000/api/v1';
      if (await verifyServer(candidateUrl)) {
        return candidateUrl;
      }
    }

    return null;
  }

  /// Extract local IPv4 subnet prefix (e.g. "192.168.1" or "192.168.11")
  Future<String?> _findLocalSubnet() async {
    try {
      final interfaces = await NetworkInterface.list(
        includeLoopback: false,
        type: InternetAddressType.IPv4,
      );

      for (var interface in interfaces) {
        for (var addr in interface.addresses) {
          final ip = addr.address;
          if (!ip.startsWith('127.') && !ip.startsWith('169.254.')) {
            final parts = ip.split('.');
            if (parts.length == 4) {
              return '${parts[0]}.${parts[1]}.${parts[2]}';
            }
          }
        }
      }
    } catch (_) {}
    return null;
  }

  /// Fast TCP port scanner for port 8000 across 254 addresses
  Future<String?> _scanSubnetForPort8000(String subnetPrefix, {Function(String)? onProgress}) async {
    const port = 8000;
    const batchSize = 30;
    const timeout = Duration(milliseconds: 250);

    for (int start = 1; start <= 254; start += batchSize) {
      final end = (start + batchSize - 1).clamp(1, 254);
      onProgress?.call('فحص الأجهزة ($start-$end من 254)...');

      final futures = <Future<String?>>[];
      for (int i = start; i <= end; i++) {
        final ip = '$subnetPrefix.$i';
        futures.add(_checkPortOpen(ip, port, timeout));
      }

      final results = await Future.wait(futures);
      for (var ip in results) {
        if (ip != null) {
          // Double check with ping
          if (await verifyServer('http://$ip:$port/api/v1', timeout: const Duration(milliseconds: 600))) {
            return ip;
          }
        }
      }
    }
    return null;
  }

  Future<String?> _checkPortOpen(String ip, int port, Duration timeout) async {
    try {
      final socket = await Socket.connect(ip, port, timeout: timeout);
      socket.destroy();
      return ip;
    } catch (_) {
      return null;
    }
  }
}

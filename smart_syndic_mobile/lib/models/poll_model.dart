class PollModel {
  final String id;
  final String title;
  final String? description;
  final String expiresAt;
  final bool isActive;
  final List<PollOptionModel> options;
  final bool hasVoted;
  final String? userVotedOptionId;

  PollModel({
    required this.id,
    required this.title,
    this.description,
    required this.expiresAt,
    required this.isActive,
    required this.options,
    this.hasVoted = false,
    this.userVotedOptionId,
  });

  int get totalVotes => options.fold(0, (sum, opt) => sum + opt.votesCount);

  factory PollModel.fromJson(Map<String, dynamic> json) {
    var optList = <PollOptionModel>[];
    if (json['options'] != null && json['options'] is List) {
      optList = (json['options'] as List)
          .map((o) => PollOptionModel.fromJson(o))
          .toList();
    }

    return PollModel(
      id: json['id'] ?? '',
      title: json['title'] ?? '',
      description: json['description'],
      expiresAt: json['expires_at'] ?? '',
      isActive: json['is_active'] == true || json['is_active'] == 1,
      options: optList,
      hasVoted: json['has_voted'] == true,
      userVotedOptionId: json['user_voted_option_id'],
    );
  }
}

class PollOptionModel {
  final String id;
  final String optionText;
  final int votesCount;
  final List<Map<String, dynamic>> detailedVotes;

  PollOptionModel({
    required this.id,
    required this.optionText,
    required this.votesCount,
    this.detailedVotes = const [],
  });

  factory PollOptionModel.fromJson(Map<String, dynamic> json) {
    return PollOptionModel(
      id: json['id'] ?? '',
      optionText: json['option_text'] ?? '',
      votesCount: json['votes_count'] ?? json['vote_count'] ?? json['votes']?.length ?? 0,
      detailedVotes: json['detailed_votes'] != null ? List<Map<String, dynamic>>.from(json['detailed_votes']) : [],
    );
  }
}

<?php

namespace App\Filament\Syndic\Resources;

use App\Filament\Syndic\Resources\SuggestionResource\Pages;
use App\Models\Poll;
use App\Models\PollOption;
use App\Models\Suggestion;
use App\Events\PollCreated;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\DB;

class SuggestionResource extends Resource
{
    protected static ?string $model = Suggestion::class;

    protected static ?string $navigationIcon = 'heroicon-o-light-bulb';

    protected static ?string $navigationLabel = 'الاقتراحات والشكايات';

    protected static ?string $navigationGroup = 'التواصل والتصويت';

    protected static ?int $navigationSort = 1;

    public static function getModelLabel(): string
    {
        return 'اقتراح / شكاية';
    }

    public static function getPluralModelLabel(): string
    {
        return 'الاقتراحات والشكايات';
    }

    public static function canCreate(): bool
    {
        return false; // Suggestions are submitted by residents only
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('عنوان الاقتراح')
                    ->searchable()
                    ->sortable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('user.full_name')
                    ->label('صاحب الاقتراح')
                    ->searchable(),
                Tables\Columns\TextColumn::make('apartment.unit_number')
                    ->label('الشقة'),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('الحالة')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'PENDING'           => 'قيد المراجعة',
                        'ACCEPTED'          => 'مقبول',
                        'REJECTED'          => 'مرفوض',
                        'CONVERTED_TO_POLL' => 'تم تحويله لتصويت',
                        default             => $state,
                    })
                    ->colors([
                        'warning' => 'PENDING',
                        'success' => 'ACCEPTED',
                        'danger'  => 'REJECTED',
                        'info'    => 'CONVERTED_TO_POLL',
                    ]),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإرسال')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('حالة الاقتراح')
                    ->options([
                        'PENDING'           => 'قيد المراجعة',
                        'ACCEPTED'          => 'مقبول',
                        'REJECTED'          => 'مرفوض',
                        'CONVERTED_TO_POLL' => 'تم تحويله لتصويت',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('عرض'),

                Action::make('accept')
                    ->label('قبول')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (Suggestion $r) => $r->status === 'PENDING')
                    ->modalHeading('قبول الاقتراح')
                    ->form([
                        Forms\Components\Textarea::make('admin_notes')
                            ->label('ملاحظات الإدارة (اختياري)')
                            ->maxLength(1000),
                    ])
                    ->action(fn (Suggestion $r, array $data) => $r->markAccepted($data['admin_notes'] ?? null)),

                Action::make('reject')
                    ->label('رفض')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn (Suggestion $r) => $r->status === 'PENDING')
                    ->requiresConfirmation()
                    ->modalHeading('رفض الاقتراح')
                    ->modalDescription('هل أنت متأكد من رغبتك في رفض هذا الاقتراح؟')
                    ->form([
                        Forms\Components\Textarea::make('admin_notes')
                            ->label('سبب الرفض')
                            ->maxLength(1000),
                    ])
                    ->action(fn (Suggestion $r, array $data) => $r->markRejected($data['admin_notes'] ?? null)),

                Action::make('convert_to_poll')
                    ->label('تحويل إلى تصويت')
                    ->icon('heroicon-o-chart-bar')
                    ->color('info')
                    ->visible(fn (Suggestion $r) => in_array($r->status, ['PENDING', 'ACCEPTED']))
                    ->modalHeading('تحويل الاقتراح إلى تصويت مجتمعي')
                    ->modalDescription('سيتم إنشاء استطلاع رأي تلقائياً مبني على هذا الاقتراح لتمكين السكان من التصويت عليه.')
                    ->form([
                        Forms\Components\TagsInput::make('options')
                            ->label('خيارات التصويت (على الأقل خيارين)')
                            ->placeholder('أدخل خياراً واضغط Enter')
                            ->required()
                            ->rule('min:2'),
                        Forms\Components\DateTimePicker::make('expires_at')
                            ->label('تاريخ ووقت انتهاء التصويت')
                            ->required()
                            ->minDate(now()),
                    ])
                    ->action(function (Suggestion $record, array $data) {
                        DB::transaction(function () use ($record, $data) {
                            $record->convertToPoll();

                            $poll = Poll::create([
                                'tenant_id'   => $record->tenant_id,
                                'created_by'  => auth()->id(),
                                'title'       => $record->title,
                                'description' => $record->description,
                                'expires_at'  => $data['expires_at'],
                                'is_active'   => true,
                            ]);

                            foreach ($data['options'] as $optionText) {
                                PollOption::create([
                                    'poll_id'     => $poll->id,
                                    'option_text' => $optionText,
                                ]);
                            }

                            event(new PollCreated($poll));
                        });
                    }),
            ]);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('title')->label('عنوان الاقتراح')->disabled(),
            Forms\Components\Textarea::make('description')->label('تفاصيل الاقتراح')->disabled(),
            Forms\Components\Textarea::make('admin_notes')->label('ملاحظات الإدارة')->maxLength(1000),
            Forms\Components\Select::make('status')
                ->label('الحالة')
                ->options([
                    'PENDING'  => 'قيد المراجعة',
                    'ACCEPTED' => 'مقبول',
                    'REJECTED' => 'مرفوض',
                ]),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSuggestions::route('/'),
        ];
    }
}

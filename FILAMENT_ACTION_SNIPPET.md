# Filament Receipt Action

The existing receipt action can remain unchanged when it already uses the
`sales-payments.receipt` route.

For `SalesPaymentResource::table()`, the complete action is:

```php
Tables\Actions\Action::make('receipt')
    ->label('Receipt')
    ->icon('heroicon-o-receipt-refund')
    ->color('success')
    ->visible(
        fn (): bool =>
            auth()->user()?->can(
                'print sales payments'
            )
            ?? false
    )
    ->url(
        fn (
            \App\Models\Sales\SalesPayment $record
        ): string =>
            route(
                'sales-payments.receipt',
                $record
            )
    )
    ->openUrlInNewTab();
```

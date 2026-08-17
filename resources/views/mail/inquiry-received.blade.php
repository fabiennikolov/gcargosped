<x-mail::message>
# Ново запитване от сайта

Получено на {{ $inquiry->created_at?->timezone(config('app.timezone'))->format('d.m.Y г. в H:i') }} ч.

<x-mail::table>
| | |
| :--- | :--- |
@foreach ($rows as $label => $value)
| **{{ $label }}** | {{ $value }} |
@endforeach
</x-mail::table>

@if (filled($inquiry->message))
**Съобщение**

> {{ $inquiry->message }}
@endif

<x-mail::button :url="$adminUrl">
Отвори в администрацията
</x-mail::button>

@if (filled($inquiry->email))
Отговор на този имейл отива директно до {{ $inquiry->email }}.
@else
Клиентът не е оставил имейл — свържете се на {{ $inquiry->phone }}.
@endif
</x-mail::message>

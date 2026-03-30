@props([
    'url',
    'color' => 'primary',
    'align' => 'center',
])
@php
$btnColors = [
    'primary' => ['bg' => '#2563eb', 'border' => '#2563eb', 'text' => '#ffffff'],
    'success' => ['bg' => '#16a34a', 'border' => '#16a34a', 'text' => '#ffffff'],
    'error'   => ['bg' => '#dc2626', 'border' => '#dc2626', 'text' => '#ffffff'],
];
$btnColor = $btnColors[$color] ?? $btnColors['primary'];
@endphp
<table class="action" align="{{ $align }}" width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin: 28px 0;">
<tr>
<td align="{{ $align }}">
<table width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td align="{{ $align }}">
<table border="0" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td style="border-radius: 8px; background-color: {{ $btnColor['bg'] }}; mso-padding-alt: 0;">
<a href="{{ $url }}" class="button button-{{ $color }}" target="_blank" rel="noopener" style="display: inline-block; padding: 12px 28px; border-radius: 8px; font-size: 15px; font-weight: 600; color: {{ $btnColor['text'] }} !important; background-color: {{ $btnColor['bg'] }}; border: 2px solid {{ $btnColor['border'] }}; text-decoration: none !important; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;">{!! $slot !!}</a>
</td>
</tr>
</table>
</td>
</tr>
</table>
</td>
</tr>
</table>

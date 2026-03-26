@props(['url'])
<tr>
<td class="header" style="background-color: #2563eb; padding: 28px 40px; text-align: center; border-radius: 10px 10px 0 0;">
<a href="{{ $url }}" style="color: #ffffff; text-decoration: none; display: inline-block; line-height: 1;">
    <table cellpadding="0" cellspacing="0" role="presentation" style="margin: 0 auto;">
    <tr>
        <td style="vertical-align: middle; padding-right: 10px;">
            <img src="{{ asset('images/brand/onlyfix-icon.svg') }}" width="40" height="40" alt="{{ config('app.name') }} ikon" style="width: 40px; height: 40px; border-radius: 8px; display: block;">
        </td>
        <td style="vertical-align: middle;">
            <span style="font-size: 22px; font-weight: 700; color: #ffffff; letter-spacing: -0.3px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;">{{ config('app.name') }}</span>
        </td>
    </tr>
    </table>
</a>
</td>
</tr>

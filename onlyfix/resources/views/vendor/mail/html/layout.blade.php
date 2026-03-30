<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
<title>{{ config('app.name') }}</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta name="color-scheme" content="light">
<meta name="supported-color-schemes" content="light">
<style>
/* Reset */
* { box-sizing: border-box; }

/* OnlyFix Brand Colors:
   Primary Blue:  #2563eb (hsl 221 83% 53%)
   Dark Blue:     #1e40af
   Accent Orange: #f97316
   Text:          #0f172a
   Muted text:    #64748b
   Border:        #e2e8f0
   Background:    #f1f5fb
*/

body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
    font-size: 16px;
    line-height: 1.6;
    color: #0f172a;
    background-color: #f1f5fb;
    margin: 0;
    padding: 0;
    -webkit-text-size-adjust: none;
}

.wrapper {
    background-color: #f1f5fb;
    width: 100%;
}

.content {
    width: 100%;
    max-width: 600px;
    margin: 0 auto;
}

/* Header */
.header {
    background-color: #2563eb;
    padding: 28px 40px;
    text-align: center;
    border-radius: 10px 10px 0 0;
}

.header a {
    color: #ffffff;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 10px;
}

.logo {
    width: 40px;
    height: 40px;
    border-radius: 8px;
}

.header-brand {
    font-size: 22px;
    font-weight: 700;
    color: #ffffff;
    letter-spacing: -0.3px;
}

/* Body */
.body {
    background-color: #f1f5fb;
    padding: 8px 0;
}

.inner-body {
    background-color: #ffffff;
    border-radius: 0;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08), 0 1px 2px rgba(0, 0, 0, 0.04);
    width: 570px;
    margin: 0 auto;
}

.content-cell {
    padding: 36px 40px;
    color: #0f172a;
    font-size: 16px;
    line-height: 1.65;
}

/* Typography */
.content-cell h1 {
    color: #0f172a;
    font-size: 22px;
    font-weight: 700;
    margin-top: 0;
    margin-bottom: 16px;
}

.content-cell h2 {
    color: #1e40af;
    font-size: 18px;
    font-weight: 600;
    margin-top: 24px;
    margin-bottom: 12px;
}

.content-cell p {
    color: #334155;
    font-size: 16px;
    line-height: 1.65;
    margin-top: 0;
    margin-bottom: 16px;
}

.content-cell a {
    color: #2563eb;
    text-decoration: underline;
}

.content-cell strong {
    color: #0f172a;
    font-weight: 600;
}

.content-cell hr {
    border: none;
    border-top: 1px solid #e2e8f0;
    margin: 24px 0;
}

/* Greeting */
.greeting {
    font-size: 18px;
    font-weight: 600;
    color: #0f172a;
    margin-bottom: 20px;
}

/* Buttons */
.action {
    margin: 28px 0;
}

.button {
    display: inline-block;
    padding: 12px 28px;
    border-radius: 8px;
    font-size: 15px;
    font-weight: 600;
    text-decoration: none !important;
    mso-padding-alt: 0;
    text-align: center;
}

.button-primary {
    background-color: #2563eb;
    color: #ffffff !important;
    border: 2px solid #2563eb;
}

.button-success {
    background-color: #16a34a;
    color: #ffffff !important;
    border: 2px solid #16a34a;
}

.button-error {
    background-color: #dc2626;
    color: #ffffff !important;
    border: 2px solid #dc2626;
}

/* Panel */
.panel {
    background-color: #eff6ff;
    border-left: 4px solid #2563eb;
    border-radius: 0 8px 8px 0;
    margin: 20px 0;
    padding: 16px 20px;
}

.panel-content {
    color: #1e40af;
    font-size: 15px;
}

/* Subcopy */
.subcopy {
    border-top: 1px solid #e2e8f0;
    padding-top: 20px;
    margin-top: 24px;
}

.subcopy p {
    color: #64748b !important;
    font-size: 13px !important;
    line-height: 1.5 !important;
}

.subcopy a {
    color: #2563eb;
    font-size: 13px;
    word-break: break-all;
}

/* Footer */
.footer {
    background-color: #f8fafc;
    border-top: 1px solid #e2e8f0;
    border-radius: 0 0 10px 10px;
    padding: 0;
    width: 570px;
    margin: 0 auto;
}

.footer .content-cell {
    padding: 20px 40px;
    text-align: center;
}

.footer p {
    color: #94a3b8 !important;
    font-size: 13px !important;
    line-height: 1.5 !important;
    margin: 0 !important;
}

.footer a {
    color: #64748b;
    font-size: 13px;
}

/* Table */
.table table {
    width: 100%;
    border-collapse: collapse;
    font-size: 15px;
}

.table table th {
    background-color: #f1f5f9;
    color: #475569;
    font-size: 13px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    padding: 10px 14px;
    text-align: left;
    border-bottom: 2px solid #e2e8f0;
}

.table table td {
    color: #334155;
    padding: 12px 14px;
    border-bottom: 1px solid #e2e8f0;
}

/* Responsive */
@media only screen and (max-width: 600px) {
    .inner-body {
        width: 100% !important;
        border-radius: 0 !important;
    }
    .footer {
        width: 100% !important;
        border-radius: 0 !important;
    }
    .header {
        border-radius: 0 !important;
        padding: 20px 24px !important;
    }
    .content-cell {
        padding: 24px 20px !important;
    }
}

@media only screen and (max-width: 500px) {
    .button {
        width: 100% !important;
        display: block !important;
    }
}
</style>
{!! $head ?? '' !!}
</head>
<body>

<table class="wrapper" width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background-color: #f1f5fb; width: 100%;">
<tr>
<td align="center" style="padding: 32px 16px;">
<table class="content" width="100%" cellpadding="0" cellspacing="0" role="presentation" style="max-width: 600px; margin: 0 auto;">
{!! $header ?? '' !!}

<!-- Email Body -->
<tr>
<td class="body" width="100%" cellpadding="0" cellspacing="0" style="border: hidden !important; background-color: #f1f5fb;">
<table class="inner-body" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation" style="background-color: #ffffff; width: 570px; margin: 0 auto; box-shadow: 0 1px 3px rgba(0,0,0,0.08);">
<!-- Body content -->
<tr>
<td class="content-cell" style="padding: 36px 40px; color: #0f172a; font-size: 16px; line-height: 1.65;">
{!! Illuminate\Mail\Markdown::parse($slot) !!}

{!! $subcopy ?? '' !!}
</td>
</tr>
</table>
</td>
</tr>

{!! $footer ?? '' !!}
</table>
</td>
</tr>
</table>
</body>
</html>

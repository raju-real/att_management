<!DOCTYPE html>
<html>

<head>
    <title>@yield('title', '')</title>
    <link rel="shortcut icon" href="{{ asset(siteSettings()['favicon'] ?? ecommerceIcon()) }}">
    <style>
        @page {
            header: page-header;
            footer: page-footer;
            margin-top: 100px;
            /* adjust this as needed for your header height */
            margin-bottom: 50px;
            /* adjust for footer height */
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12px;
            color: #100f0f;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 100%;
            box-sizing: border-box;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th,
        .table td {
            padding: 8px 8px;
            text-align: left;
            color: #100f0f;
            text-align: left;
            font-size: 13px;
            padding: 5px;
            border-left: 1px solid #eff2f7;
            border-bottom: 1px solid #eff2f7;
            border-right: 1px solid #eff2f7;
        }
    </style>
    @stack('css')
</head>

<body>
    <htmlpageheader name="page-header">
        <table width="100%" style="border-bottom: 1px solid #ddd; padding-bottom: 5px;">
            <tr>
                <td style="text-align: left;vertical-align: top;">
                    <img src="{{ asset(siteSettings()['logo']) }}" width="150">
                </td>
                <td style="text-align: right;vertical-align: top;">
                    Page {PAGENO} of {nbpg}
                </td>
            </tr>
        </table>

    </htmlpageheader>

    <div class="container">
        @yield('content')
    </div>

    <htmlpagefooter name="page-footer">
        <table width="100%" style="border-top: 1px solid #ddd; padding-top: 5px;color: #100f0f;font-size: 13px;">
            <tr>
                <td width="33%" style="text-align: left; vertical-align: top;">
                    <strong style="color: #EA6A39;font-size: 15px;">{{ siteSettings()['company_name'] ?? "" }}</strong><br>
                    {{ siteSettings()['address'] ?? "" }}
                </td>
                <td width="33%" style="text-align: left; vertical-align: top;">
                    <strong>Find us</strong><br>
                    {{ siteSettings()['facebook_url'] ?? "" }}<br>
                    {{ siteSettings()['linkedin_url'] ?? "" }}<br>
                </td>
                <td width="33%" style="text-align: left; vertical-align: top;">
                    <strong>Contact</strong><br>
                    Email: {{ siteSettings()['company_email'] ?? "" }}<br>
                    Mobile: {{ siteSettings()['company_mobile'] ?? siteSettings()['company_phone'] ?? "" }}
                </td>
            </tr>
        </table>
    </htmlpagefooter>
</body>

</html>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD XHTML 1.0 Transitional //EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <!--[if gte mso 9]>
    <xml>
      <o:OfficeDocumentSettings>
        <o:AllowPNG/>
        <o:PixelsPerInch>96</o:PixelsPerInch>
      </o:OfficeDocumentSettings>
    </xml>
    <![endif]-->
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="x-apple-disable-message-reformatting">
    <!--[if !mso]><!--><meta http-equiv="X-UA-Compatible" content="IE=edge"><!--<![endif]-->
    <title>{{ config('app.name') }}</title>
    <style type="text/css">
        @media only screen and (min-width: 620px) {
            .u-row { width: 600px !important; }
            .u-row .u-col { vertical-align: top; }
            .u-row .u-col-100 { width: 600px !important; }
        }
        @media only screen and (max-width: 620px) {
            .u-row-container { max-width: 100% !important; padding-left: 0px !important; padding-right: 0px !important; }
            .u-row { width: 100% !important; }
            .u-row .u-col { display: block !important; width: 100% !important; min-width: 320px !important; max-width: 100% !important; }
            .u-row .u-col > div { margin: 0 auto; }
            .u-row .u-col img { max-width: 100% !important; }
        }
        body { margin: 0; padding: 0; -webkit-text-size-adjust: 100%; background-color: #e7e7e7; color: #000000; }
        table, td, tr { border-collapse: collapse; vertical-align: top; }
        .ie-container table, .mso-container table { table-layout: fixed; }
        * { line-height: inherit; }
        a[x-apple-data-detectors=true] { color: inherit !important; text-decoration: none !important; }
        
        /* Custom Button Style */
        .btn-primary {
            box-sizing: border-box; display: inline-block; font-family: arial,helvetica,sans-serif; text-decoration: none; 
            -webkit-text-size-adjust: none; text-align: center; color: #FFFFFF; background-color: #008060; 
            border-radius: 4px; -webkit-border-radius: 4px; -moz-border-radius: 4px; width: auto; max-width: 100%; 
            overflow-wrap: break-word; word-break: break-word; word-wrap:break-word; mso-border-alt: none;
            padding: 12px 30px; font-size: 16px; font-weight: bold;
        }
    </style>
    <!--[if !mso]><!--><link href="https://fonts.googleapis.com/css?family=Raleway:400,700&display=swap" rel="stylesheet" type="text/css"><!--<![endif]-->
</head>

<body class="clean-body u_body" style="margin: 0;padding: 0;-webkit-text-size-adjust: 100%;background-color: #e7e7e7;color: #000000">
    <table role="presentation" style="border-collapse: collapse;table-layout: fixed;border-spacing: 0;mso-table-lspace: 0pt;mso-table-rspace: 0pt;vertical-align: top;min-width: 320px;Margin: 0 auto;background-color: #e7e7e7;width:100%" cellpadding="0" cellspacing="0">
        <tbody>
            <tr style="vertical-align: top">
                <td style="word-break: break-word;border-collapse: collapse !important;vertical-align: top">
                    
                    <!-- HEADER SECTION -->
                    <div class="u-row-container" style="padding: 0px;background-color: transparent">
                        <div class="u-row" style="margin: 0 auto;min-width: 320px;max-width: 600px;background-color: transparent;">
                            <div style="border-collapse: collapse;display: table;width: 100%;height: 100%;background-color: transparent;">
                                <div class="u-col u-col-100" style="max-width: 320px;min-width: 600px;display: table-cell;vertical-align: top;">
                                    <div style="background-color: #f4f4f4; height: 100%; display: block;">
                                        <table style="font-family:arial,helvetica,sans-serif;" role="presentation" cellpadding="0" cellspacing="0" width="100%" border="0">
                                            <tbody>
                                                <tr>
                                                    <td style="padding: 30px 10px; text-align: center;">
                                                        <h1 style="margin: 0; font-size: 24px; color: #008060;">{{ config('app.name') }}</h1>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- BODY CONTENT SECTION -->
                    <div class="u-row-container" style="padding: 0px;background-color: transparent">
                        <div class="u-row" style="margin: 0 auto;min-width: 320px;max-width: 600px;background-color: #ffffff;">
                            <div style="border-collapse: collapse;display: table;width: 100%;height: 100%;background-color: transparent;">
                                <div class="u-col u-col-100" style="max-width: 320px;min-width: 600px;display: table-cell;vertical-align: top;">
                                    <div style="background-color: #ffffff;height: 100%; display: block; padding: 30px;">
                                        
                                        <!-- Banner Image (Optional) -->
                                        @if(isset($bannerImage))
                                        <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td align="center" style="padding-bottom: 20px;">
                                                    <img src="{{ $bannerImage }}" alt="Banner" style="width: 100%; max-width: 540px; height: auto; border-radius: 4px;">
                                                </td>
                                            </tr>
                                        </table>
                                        @endif

                                        <!-- Content Injection -->
                                        @yield('content')
                                        
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- FOOTER SECTION -->
                    <div class="u-row-container" style="padding: 0px;background-color: transparent">
                        <div class="u-row" style="margin: 0 auto;min-width: 320px;max-width: 600px;background-color: transparent;">
                            <div style="border-collapse: collapse;display: table;width: 100%;height: 100%;background-color: transparent;">
                                <div class="u-col u-col-100" style="max-width: 320px;min-width: 600px;display: table-cell;vertical-align: top;">
                                    <div style="height: 100%;display: block !important;">
                                        <table style="font-family:arial,helvetica,sans-serif;" role="presentation" cellpadding="0" cellspacing="0" width="100%" border="0">
                                            <tbody>
                                                <tr>
                                                    <td style="padding: 30px 20px; text-align: center; font-size: 12px; color: #888888; line-height: 1.6;">
                                                        <p style="margin: 0;">Need help? Contact us at <a href="mailto:{{ config('msdev2.contact_email') }}" style="color: #008060; text-decoration: underline;">{{ config('msdev2.contact_email') }}</a></p>
                                                        <p style="margin: 10px 0 0;">&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </td>
            </tr>
        </tbody>
    </table>
</body>
</html>
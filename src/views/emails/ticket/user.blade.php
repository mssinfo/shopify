<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html charset=UTF-8" />
    <title>Email</title>
</head>
<body>
    <!--wrapper grey-->
    <table align="center" bgcolor="#EAECED" border="0" cellpadding="0" cellspacing="0" width="600">
        <tbody>
            <!--spacing-->
            <tr>
                <td>&nbsp;</td>
            </tr>
            <tr>
                <td>&nbsp;</td>
            </tr>
            <tr>
                <td>&nbsp;</td>
            </tr>
            <!--First  table section with logo-->
            <tr>
                <td align="center" valign="top">
                    <table width="600">
                        <tbody>
                            <tr>
                                <td align="center" valign="top">
                                    <table bgcolor="#FFFFFF" border="0" cellpadding="0" cellspacing="0"
                                        style="overflow:hidden!important; border-radius:3px" width="600">
                                        <tbody>
                                            <tr style="background: #333333; line-height: 3.5">
                                                <td align="Left" valign="top" style="width: 55px; padding:1px 5px 5px 32px;">
                                                    <p style="font-family:Arial;font-style:normal;font-weight:bold;font-size:14px;text-align:left;color:#ffffff; padding:1px 32px 5px 4px;">
                                                        <span> {{ $heading }} </span>
                                                    </p>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <!--Separate table for header and content-->
                    <table bgcolor="#FFFFFF" border="0" cellpadding="0" cellspacing="0" width="600">
                        <tbody>
                            <tr>
                                <td align="center">
                                    <table  width="600">
                                        <tbody>
                                            <tr>
                                                <td align="left" style="font-family:Arial;font-style:normal;font-weight:normal;line-height:22px;font-size:14px;color:#333333;">
                                                    Dear shop owner<br>
                                                    I hope this email finds you well.<br><br>

                                                    I am writing to inform you that your ticket has been successfully created in our system. Our representative will reach out to you within the next 24 hours to address your query or concern.<br><br>
                                                    
                                                    We appreciate you taking the time to contact us. Your satisfaction is our priority, and we are committed to resolving any issues you may have.<br><br>
                                                    
                                                    Thank you for reaching out to us.<br><br>
                                                    
                                                    Warm regards,<br><br>
                                                    
                                                    {{config('msdev2.contact_url')}}<br>
                                                    {{config('msdev2.contact_email')}}<br>
                                                </td>
                                            </tr>
                                            <!--spacing-->
                                            <tr>
                                                <td>&nbsp;</td>
                                            </tr>
                                            <tr>
                                                <td>&nbsp;</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </td> <!--first table section td ending-->
                <!--outer spacing-->
            </tr>
            <tr>
                <td>&nbsp;</td>
            </tr>
            <tr>
                <td>&nbsp;</td>
            </tr>
        </tbody>
    </table> <!-- - main tabel grey bg-->
</body>
</html>

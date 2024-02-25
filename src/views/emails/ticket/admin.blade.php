<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html charset=UTF-8" />
    <title>Email</title>
</head>
<body>
    <!--wrapper grey-->
    <table align="center" bgcolor="#EAECED" border="0" cellpadding="0" cellspacing="0" width="100%">
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
                                                    <p style="font-family:Arial;font-style:normal;font-weight:bold;font-size:14px;text-align:right;color:#ffffff; padding:1px 32px 5px 4px;">
                                                        <span> {{ $heading }} </span>
                                                    </p>
                                                </td>
                                                <td align="Left" valign="top">
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
                                    <table width="85%">
                                        <tbody>
                                            <tr>
                                                <td align="left" style="font-family:Arial;font-style:normal;font-weight:normal;line-height:22px;font-size:14px;color:#333333;">
                                                   Hi Admin<br>
                                                   New Ticket has been create in {{ $shop }}
                                                   <table>
                                                        <tr> <td>Email</td><td>{{$data["email"]}}</td></tr>
                                                        @if ($data["subject"]) 
                                                            <tr> <td>Subject</td><td>{{$data["subject"]}}</td> </tr>
                                                        @endif
                                                        @if ($data["category"]) 
                                                            <tr> <td>Category</td><td>{{$data["category"]}}</td> </tr>
    
                                                        @endif                                                        
                                                        @if ($data["detail"]) 
                                                            <tr> <td>Detail</td><td>{{$data["detail"]}}</td> </tr>
                                                        @endif
                                                        @if ($data["password"]) 
                                                            <tr> <td>Password</td><td>{{$data["password"]}}</td> </tr>
                                                        @endif
                                                        @if ($data["priority"]) 
                                                            <tr> <td>Priority</td><td>{{$data["priority"]}}</td> </tr>
                                                        @endif
                                                        <tr> <td>IP</td><td>{{request()->ip()}}</td> </tr>
                                                   </table>
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

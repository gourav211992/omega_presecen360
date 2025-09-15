<table style="width: 100%; margin-bottom: 10px;" cellspacing="0" cellpadding="0">
    <tr>
        <td style="text-align: left; width: 33%;">
            @if (isset($orgLogo) && $orgLogo)
                @php
                    $data = isset($orgLogo) && $orgLogo ? file_get_contents($orgLogo) : '';
                    $imgType = pathinfo($orgLogo, PATHINFO_EXTENSION);
                    $base64 = 'data:image/' . $imgType . ';base64,' . base64_encode($data);
                @endphp
                <img src="{!! $base64 !!}" alt="" height="50px" />
            @endif
        </td>
        <td style="text-align: center; width: 34%; font-weight: bold; font-size: 22px;">
            {{ $moduleTitle ?? 'Document' }}
        </td>
        <td style="width: 33%;"></td>
    </tr>
</table>
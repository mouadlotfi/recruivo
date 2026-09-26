@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block; text-decoration: none;">
<img src="{{ asset('images/logo.png') }}" class="logo" alt="" width="28" height="28">
<span class="logo-wordmark">{!! $slot !!}</span>
</a>
</td>
</tr>

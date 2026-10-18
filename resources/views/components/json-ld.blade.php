@props(['data'])
{{-- JSON_HEX_TAG keeps "</script>" inside text from closing the tag. --}}
<script type="application/ld+json">{!! json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>

@php
    $passwordId = $passwordId ?? 'password';
    $confirmId = $confirmId ?? null;
@endphp

<div id="pw-criteria-{{ $passwordId }}" class="mt-2 text-sm">
    <ul>
        <li class="pw-crit-length text-red-600">• Minimum 8 characters</li>
        <li class="pw-crit-match text-red-600" style="{{ $confirmId ? '' : 'display:none;' }}">• Passwords match</li>
    </ul>
</div>

<script>
(function(){
    const pw = document.getElementById('{{ $passwordId }}');
    if (!pw) return;
    const crit = document.getElementById('pw-criteria-{{ $passwordId }}');
    const lenEl = crit.querySelector('.pw-crit-length');
    const matchEl = crit.querySelector('.pw-crit-match');
    const conf = {{ $confirmId ? 'document.getElementById("' . $confirmId . '")' : 'null' }};

    function update() {
        const v = pw.value || '';
        if (v.length >= 8) {
            lenEl.classList.remove('text-red-600');
            lenEl.classList.add('text-green-600');
        } else {
            lenEl.classList.remove('text-green-600');
            lenEl.classList.add('text-red-600');
        }

        if (conf) {
            const cv = conf.value || '';
            if (v !== '' && cv !== '' && v === cv) {
                matchEl.classList.remove('text-red-600');
                matchEl.classList.add('text-green-600');
            } else {
                matchEl.classList.remove('text-green-600');
                matchEl.classList.add('text-red-600');
            }
        }
    }

    pw.addEventListener('input', update);
    if (conf) conf.addEventListener('input', update);
    // initial run
    document.addEventListener('DOMContentLoaded', update);
})();
</script>

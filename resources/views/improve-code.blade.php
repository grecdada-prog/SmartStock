@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Améliorer mon code avec Mistral</h1>

    <form id="codeForm">
        @csrf
        <div class="form-group">
            <label for="code">Colle ton code ici :</label>
            <textarea class="form-control" id="code" name="code" rows="10" required></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Améliorer le code</button>
    </form>

    <div id="result" class="mt-4" style="display: none;">
        <h3>Code amélioré :</h3>
        <pre id="improvedCode" class="bg-light p-3 rounded"></pre>
    </div>
</div>

<script>
document.getElementById('codeForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const code = document.getElementById('code').value;
    const resultDiv = document.getElementById('result');

    try {
        const response = await fetch('/improve-code', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
            },
            body: JSON.stringify({ code }),
        });

        const data = await response.json();

        if (data.error) {
            alert(data.error);
            return;
        }

        document.getElementById('improvedCode').textContent = data.improved_code;
        resultDiv.style.display = 'block';
    } catch (error) {
        alert('Une erreur est survenue : ' + error.message);
    }
});
</script>
@endsection

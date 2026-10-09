<!-- Tombol lihat/sembunyikan password -->
  <script>
    document.querySelectorAll('.toggle-pass').forEach(function (el) {
      el.addEventListener('click', function () {
        var input = document.getElementById(el.dataset.target);
        var icon = el.querySelector('i');
        var tampil = input.type === 'password';
        input.type = tampil ? 'text' : 'password';
        icon.className = tampil ? 'bi bi-eye-slash' : 'bi bi-eye';
      });
    });
  </script>
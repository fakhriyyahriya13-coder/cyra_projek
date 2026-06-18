</div> </div> <footer class="sticky-footer bg-white" style="padding: 20px 0; border-top: 1px solid #e3e6f0; margin-top: 50px;">
    <div class="container my-auto">
        <div class="copyright text-center my-auto">
            <span>Copyright &copy; CYRA - Chatbot Akademik 2026</span>
        </div>
    </div>
</footer>

<script src="../../assets/vendor/jquery/jquery.min.js"></script>
<script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

<script src="../../assets/js/cyra-chat.js"></script>

<script>
    // Script Dropdown Sidebar yang tadi kita perbaiki
    document.querySelectorAll('.dropdown > span').forEach(item => {
        item.addEventListener('click', function(e) {
            const parent = this.parentElement;

            // Tutup dropdown lain (Accordion mode)
            document.querySelectorAll('.dropdown.open').forEach(openItem => {
                if (openItem !== parent) {
                    openItem.classList.remove('open');
                }
            });

            // Toggle menu yang diklik
            parent.classList.toggle('open');
        });
    });
</script>

</body>
</html>
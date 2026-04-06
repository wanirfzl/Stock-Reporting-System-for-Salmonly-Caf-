<?php
// includes/footer.php
?>
            <!-- Footer -->
            <footer class="mt-auto px-10 py-10 text-center">
                <div class="w-24 h-px bg-[#3C2A21]/10 mx-auto mb-6"></div>
                <p class="text-[#3C2A21]/30 text-[10px] font-black uppercase tracking-[0.4em]">© <?php echo date('Y'); ?> Salmonly Café • Centralized Stock System</p>
            </footer>
        </div>
    </div>

    <script>
        // Global functions
        function showNotifications() {
            alert('🔔 Notifications\n\n• Salmon restocked (08:30 AM)\n• Counter Audit (07:15 AM)\n• Waste Recorded (06:45 AM)\n• Supplier Arrival (06:00 AM)');
        }

        function showSuccess(message) {
            alert('✅ Success: ' + message);
        }

        function showError(message) {
            alert('❌ Error: ' + message);
        }

        function confirmAction(message, callback) {
            if (confirm(message)) {
                callback();
            }
        }
    </script>
</body>
</html>

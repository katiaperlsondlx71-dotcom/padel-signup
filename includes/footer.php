    </main>
    
    <footer style="margin-top: 4rem; padding: 2rem 0; background: var(--white); border-top: 1px solid var(--gray-200); text-align: center; color: var(--gray-600);">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> Padel Tournament Registration. Made with ❤️ for the padel community.</p>
        </div>
    </footer>
    
    <script>
        // Simple mobile menu toggle if needed
        document.addEventListener('DOMContentLoaded', function() {
            // Add any JavaScript functionality here
            
            // Auto-hide messages after 5 seconds
            const messages = document.querySelectorAll('.message');
            messages.forEach(message => {
                setTimeout(() => {
                    message.style.opacity = '0';
                    setTimeout(() => {
                        message.remove();
                    }, 300);
                }, 5000);
            });
        });

        // Copy tournament link function
        function copyTournamentLink(tournamentId) {
            const url = window.location.origin + window.location.pathname.replace('index.php', '') + 'tournament.php?id=' + tournamentId;
            
            // Create temporary textarea to copy text
            const textarea = document.createElement('textarea');
            textarea.value = url;
            document.body.appendChild(textarea);
            textarea.select();
            textarea.setSelectionRange(0, 99999);
            
            try {
                document.execCommand('copy');
                
                // Show feedback
                const button = event.target.closest('button');
                const originalText = button.innerHTML;
                button.innerHTML = '✅ Copied!';
                button.style.background = '#dcfce7';
                button.style.color = '#166534';
                
                setTimeout(() => {
                    button.innerHTML = originalText;
                    button.style.background = '';
                    button.style.color = '';
                }, 2000);
                
            } catch (err) {
                console.error('Failed to copy: ', err);
                // Fallback: show the URL in an alert
                alert('Copy this link:\n' + url);
            }
            
            document.body.removeChild(textarea);
        }
    </script>
</body>
</html>
// ============================================
// THE FOOD ECONOMIST - MASTER SCRIPT
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    
    // --- ROTATING HEADLINE LOGIC ---
    const textElement = document.getElementById('rotating-text');
    
    if (textElement) {
        const roles = [
            "UK Food Producers",
            "Brand Owners",
            "Importers",
            "Contract Packers",
            "Online Sellers",
            "Wholesalers"
        ];
        
        let index = 0;
        
        // Change text every 2.5 seconds
        setInterval(() => {
            // 1. Start Fade Out
            textElement.style.opacity = '0';
            textElement.style.transition = 'opacity 0.5s ease';
            
            setTimeout(() => {
                // 2. Change Text while invisible
                index = (index + 1) % roles.length;
                textElement.textContent = roles[index];
                
                // 3. Fade In
                textElement.style.opacity = '1';
            }, 500); // Wait 0.5s for fade out to finish
            
        }, 2500);
    }

    // --- SMOOTH SCROLL FOR ANCHOR LINKS ---
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

});

// Enhanced animations and interactions for index page

document.addEventListener('DOMContentLoaded', function() {
    // Check for reduced motion preference
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    
    // Check if device is mobile/tablet
    const isMobile = window.innerWidth <= 768;
    const isTablet = window.innerWidth <= 992 && window.innerWidth > 768;

    // Mobile Menu Toggle
    const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
    const navLinks = document.querySelector('.nav-links');
    const body = document.body;

    if (mobileMenuToggle && navLinks) {
        mobileMenuToggle.addEventListener('click', function() {
            this.classList.toggle('active');
            navLinks.classList.toggle('active');
            body.classList.toggle('menu-open');
        });

        // Close menu when clicking on a link
        const navLinkItems = navLinks.querySelectorAll('.nav-link');
        navLinkItems.forEach(link => {
            link.addEventListener('click', function() {
                mobileMenuToggle.classList.remove('active');
                navLinks.classList.remove('active');
                body.classList.remove('menu-open');
            });
        });

        // Close menu when clicking outside
        document.addEventListener('click', function(event) {
            const isClickInsideMenu = navLinks.contains(event.target);
            const isClickOnToggle = mobileMenuToggle.contains(event.target);
            
            if (!isClickInsideMenu && !isClickOnToggle && navLinks.classList.contains('active')) {
                mobileMenuToggle.classList.remove('active');
                navLinks.classList.remove('active');
                body.classList.remove('menu-open');
            }
        });

        // Close menu on window resize to desktop
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768 && navLinks.classList.contains('active')) {
                mobileMenuToggle.classList.remove('active');
                navLinks.classList.remove('active');
                body.classList.remove('menu-open');
            }
        });
    }

    // Tag List Scroller Auto-scroll - Optimized for smooth performance
    const taglistScroller = document.querySelector('.taglist-scroller');
    if (taglistScroller && !prefersReducedMotion) {
        let scrollDirection = 1;
        const scrollSpeed = 0.8; // Pixels per frame
        let isPaused = false;
        let animationFrameId = null;
        let maxScroll = 0;
        let isUserScrolling = false;
        let scrollTimeout = null;

        // Calculate max scroll once
        function updateMaxScroll() {
            maxScroll = taglistScroller.scrollWidth - taglistScroller.clientWidth;
        }
        updateMaxScroll();
        
        // Recalculate on resize
        window.addEventListener('resize', updateMaxScroll, { passive: true });

        // Pause on hover
        taglistScroller.addEventListener('mouseenter', () => {
            isPaused = true;
        });

        taglistScroller.addEventListener('mouseleave', () => {
            isPaused = false;
        });

        // Detect user scrolling
        taglistScroller.addEventListener('scroll', () => {
            isUserScrolling = true;
            clearTimeout(scrollTimeout);
            scrollTimeout = setTimeout(() => {
                isUserScrolling = false;
            }, 150);
        }, { passive: true });

        // Optimized auto-scroll function
        function autoScroll() {
            if (!isPaused && !isUserScrolling && maxScroll > 0) {
                const currentScroll = taglistScroller.scrollLeft;
                let newScroll = currentScroll + (scrollSpeed * scrollDirection);
                
                if (newScroll >= maxScroll) {
                    newScroll = maxScroll;
                    scrollDirection = -1;
                } else if (newScroll <= 0) {
                    newScroll = 0;
                    scrollDirection = 1;
                }
                
                // Direct assignment is faster than scrollTo
                taglistScroller.scrollLeft = newScroll;
            }
            
            animationFrameId = requestAnimationFrame(autoScroll);
        }

        // Start auto-scroll after a short delay
        setTimeout(() => {
            animationFrameId = requestAnimationFrame(autoScroll);
        }, 1000);

        // Cleanup on page unload
        window.addEventListener('beforeunload', () => {
            if (animationFrameId) {
                cancelAnimationFrame(animationFrameId);
            }
        });
    }
    
    // Animate registration cards on scroll
    const observerOptions = {
        threshold: 0.2,
        rootMargin: '0px 0px -100px 0px'
    };

    const observer = new IntersectionObserver(function(entries) {
        entries.forEach((entry, index) => {
            if (entry.isIntersecting) {
                setTimeout(() => {
                    entry.target.classList.add('animate');
                }, prefersReducedMotion ? 0 : index * 150);
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);

    // Observe registration cards for scroll animations (optional enhancement)
    const cards = document.querySelectorAll('.registration-card');
    
    // Cards already animate via CSS on load, so we can skip IntersectionObserver
    // or use it for additional scroll-based effects if needed
    // cards.forEach(card => {
    //     observer.observe(card);
    // });

    // Animate benefits list items on card hover (desktop only)
    if (!prefersReducedMotion && !isMobile) {
        cards.forEach(card => {
            const benefitsItems = card.querySelectorAll('.benefits-list li');
            
            card.addEventListener('mouseenter', function() {
                benefitsItems.forEach((item, index) => {
                    setTimeout(() => {
                        item.classList.add('animate');
                    }, index * 50);
                });
            });
        });
    } else {
        // Show benefits items immediately on mobile/reduced motion
        cards.forEach(card => {
            const benefitsItems = card.querySelectorAll('.benefits-list li');
            benefitsItems.forEach(item => {
                item.classList.add('animate');
            });
        });
    }

    // Add ripple effect to buttons (desktop only, skip if reduced motion)
    if (!prefersReducedMotion && !isMobile) {
        const buttons = document.querySelectorAll('.btn-primary');
        buttons.forEach(button => {
            button.addEventListener('click', function(e) {
                const ripple = document.createElement('span');
                const rect = this.getBoundingClientRect();
                const size = Math.max(rect.width, rect.height);
                const x = e.clientX - rect.left - size / 2;
                const y = e.clientY - rect.top - size / 2;
                
                ripple.style.width = ripple.style.height = size + 'px';
                ripple.style.left = x + 'px';
                ripple.style.top = y + 'px';
                ripple.classList.add('ripple');
                
                this.appendChild(ripple);
                
                setTimeout(() => {
                    ripple.remove();
                }, 600);
            });
        });
    }

    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            if (href !== '#' && href.length > 1) {
                e.preventDefault();
                const target = document.querySelector(href);
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            }
        });
    });

    // Parallax effect on scroll (subtle, desktop only, skip if reduced motion)
    if (!prefersReducedMotion && !isMobile) {
        let lastScrollTop = 0;
        window.addEventListener('scroll', function() {
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            const header = document.querySelector('.page-header');
            
            if (header) {
                const scrolled = scrollTop * 0.2;
                header.style.transform = `translateY(${scrolled}px)`;
                header.style.opacity = Math.max(0.3, 1 - (scrollTop / 500));
            }
            
            lastScrollTop = scrollTop;
        }, { passive: true });
    }

    // Add entrance animation to navbar (skip if reduced motion)
    if (!prefersReducedMotion) {
        const navbar = document.querySelector('.navbar');
        if (navbar) {
            navbar.style.animation = isMobile ? 'fadeIn 0.4s ease-out' : 'slideDown 0.6s ease-out';
        }
    }

    // Animate icons on page load (desktop only, skip if reduced motion)
    if (!prefersReducedMotion && !isMobile) {
        const icons = document.querySelectorAll('.registration-icon');
        icons.forEach((icon, index) => {
            setTimeout(() => {
                icon.style.animation = 'bounceIn 0.8s ease-out';
            }, 800 + (index * 200));
        });
    }

    // Add typing effect to page title (optional enhancement)
    // Uncomment the entire block below to enable typing effect
    /*
    const pageTitle = document.querySelector('.page-title');
    if (pageTitle) {
        const originalText = pageTitle.textContent;
        pageTitle.textContent = '';
        let charIndex = 0;
        
        function typeTitle() {
            if (charIndex < originalText.length) {
                pageTitle.textContent += originalText.charAt(charIndex);
                charIndex++;
                setTimeout(typeTitle, 50);
            }
        }
        
        setTimeout(typeTitle, 500);
    }
    */

    // Add counter animation to stats (if any are added later)
    function animateCounter(element, target, duration = 2000) {
        let start = 0;
        const increment = target / (duration / 16);
        
        const timer = setInterval(() => {
            start += increment;
            if (start >= target) {
                element.textContent = target;
                clearInterval(timer);
            } else {
                element.textContent = Math.floor(start);
            }
        }, 16);
    }

    // Enhance card hover with subtle tilt effect (optional, for modern browsers)
    // Uncomment if you want 3D tilt effect (may conflict with CSS hover)
    /*
    cards.forEach(card => {
        card.addEventListener('mousemove', function(e) {
            const rect = card.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            
            const centerX = rect.width / 2;
            const centerY = rect.height / 2;
            
            const rotateX = (y - centerY) / 30;
            const rotateY = (centerX - x) / 30;
            
            card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) translateY(-8px) scale(1.02)`;
        });
        
        card.addEventListener('mouseleave', function() {
            card.style.transform = '';
        });
    });
    */
});

// Add CSS for ripple effect via style tag
const style = document.createElement('style');
style.textContent = `
    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-100%);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .btn-primary {
        position: relative;
        overflow: hidden;
    }
    
    .btn-primary .ripple {
        position: absolute;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.6);
        transform: scale(0);
        animation: ripple-animation 0.6s ease-out;
        pointer-events: none;
    }
    
    @keyframes ripple-animation {
        to {
            transform: scale(4);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);


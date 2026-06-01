(function() {
    "use strict";

    // Configuration for particle system
    const PARTICLE_COUNT = 60; // Number of floating particles
    const PARTICLE_SIZE_MIN = 1; // minimum particle size (px)
    const PARTICLE_SIZE_MAX = 3; // maximum particle size (px)
    const PARTICLE_OPACITY_MIN = 0.2; // minimum opacity
    const PARTICLE_OPACITY_MAX = 0.6; // maximum opacity
    const ANIMATION_DURATION_MIN = 8; // seconds
    const ANIMATION_DURATION_MAX = 18; // seconds
    const ANIMATION_DELAY_MAX = 12; // seconds

    // Reference to container
    let container = null;
    let particlesCreated = false;

    // Function to generate a random number between min and max (inclusive)
    function randomRange(min, max) {
        return min + Math.random() * (max - min);
    }

    // Create a single particle element
    function createParticle() {
        const particle = document.createElement('div');
        particle.className = 'whistleguard-particle';

        // Random horizontal position (percentage)
        const leftPos = Math.random() * 100;
        particle.style.left = leftPos + '%';

        // Random size
        const size = randomRange(PARTICLE_SIZE_MIN, PARTICLE_SIZE_MAX);
        particle.style.width = size + 'px';
        particle.style.height = size + 'px';

        // Random opacity
        const opacity = randomRange(PARTICLE_OPACITY_MIN, PARTICLE_OPACITY_MAX);
        particle.style.opacity = opacity;

        // Random animation duration and delay
        const duration = randomRange(ANIMATION_DURATION_MIN, ANIMATION_DURATION_MAX);
        const delay = randomRange(0, ANIMATION_DELAY_MAX);
        particle.style.animationDuration = duration + 's';
        particle.style.animationDelay = delay + 's';

        // Optional: slight color variation (still within purple/blue theme)
        const hue = randomRange(230, 280); // bluish to purple range
        particle.style.background = `rgba(${Math.floor(79 + Math.random() * 50)}, ${Math.floor(70 + Math.random() * 60)}, ${Math.floor(229 + Math.random() * 26)}, ${opacity * 0.8})`;

        return particle;
    }

    // Generate all particles and append to container
    function generateParticles() {
        if (!container) {
            container = document.getElementById('whistleguard-root');
            if (!container) {
                console.warn('[WhistleGuard BG] Container #whistleguard-root not found. Particles not generated.');
                return;
            }
        }

        // Remove existing particles (if any) to avoid duplicates
        const existingParticles = container.querySelectorAll('.whistleguard-particle');
        existingParticles.forEach(p => p.remove());

        // Create fresh particles
        for (let i = 0; i < PARTICLE_COUNT; i++) {
            const particle = createParticle();
            container.appendChild(particle);
        }
        particlesCreated = true;
    }

    // Function to ensure the container has the required class and default shapes
    function ensureContainerAndShapes() {
        let containerEl = document.getElementById('whistleguard-root');
        if (!containerEl) {
            // Auto-create container if missing (helpful for drop-in use)
            containerEl = document.createElement('div');
            containerEl.id = 'whistleguard-root';
            containerEl.className = 'whistleguard-bg-container';
            document.body.insertBefore(containerEl, document.body.firstChild);
            console.log('[WhistleGuard BG] Auto-created background container.');
        }

        // Ensure container has the correct class
        if (!containerEl.classList.contains('whistleguard-bg-container')) {
            containerEl.classList.add('whistleguard-bg-container');
        }

        container = containerEl;

        // Check if shape elements already exist (prevent duplicate shapes)
        const existingShapes = container.querySelectorAll('.whistleguard-shape');
        if (existingShapes.length === 0) {
            // Inject the 8 floating shapes dynamically if not present
            const shapeClasses = [
                'whistleguard-shape-1', 'whistleguard-shape-2', 'whistleguard-shape-3',
                'whistleguard-shape-4', 'whistleguard-shape-5', 'whistleguard-shape-6',
                'whistleguard-shape-7', 'whistleguard-shape-8'
            ];
            shapeClasses.forEach(className => {
                const shapeDiv = document.createElement('div');
                shapeDiv.className = `whistleguard-shape ${className}`;
                container.appendChild(shapeDiv);
            });
        }
    }

    // Reinitialize particles (useful for dynamic content or resize)
    function refreshParticles() {
        generateParticles();
    }

    // Optional: add a small resize listener to optionally adjust? Not needed for core but clean.
    // Also ensures that particles stay in background.
    function initBackground() {
        ensureContainerAndShapes();
        generateParticles();

        // Add a class to body to allow optional dark background styling (non-invasive)
        if (!document.body.classList.contains('whistleguard-bg-ready')) {
            document.body.style.backgroundColor = '#0a0e1a';
            document.body.style.margin = '0';
            document.body.style.padding = '0';
            // Ensure body min-height to fill viewport
            document.body.style.minHeight = '100vh';
        }

        // Optional: If a user clicks on a refresh button (demo only), but we expose globally
        window.whistleguardRefreshParticles = refreshParticles;
    }

    // Run when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initBackground);
    } else {
        initBackground();
    }

    // Expose public methods for advanced usage
    window.WhistleGuardBackground = {
        refreshParticles: refreshParticles,
        addCustomShape: function(shapeHtmlOrElement) {
            if (container) {
                container.appendChild(shapeHtmlOrElement);
            } else {
                console.warn('Container not initialized yet.');
            }
        },
        setParticleCount: function(count) {
            // Allow dynamic particle count (recreates)
            if (typeof count === 'number' && count > 0) {
                window.PARTICLE_COUNT_OVERRIDE = count;
                generateParticles();
            }
        }
    };
})();
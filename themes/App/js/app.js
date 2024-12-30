/** contributors data - maybe move to the card itself? */
function contributorsData() {
    return {
        contributors: [],
        loading: true,
        init() {
            console.log('Fetching contributors...');
            fetch('https://api.github.com/repos/lonnieezell/Bonfire2/contributors')
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok ' + response.statusText);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Contributors fetched:', data);
                    this.contributors = data.filter(contributor => contributor.login !== 'dependabot[bot]').slice(0, 5);
                })
                .catch(error => {
                    console.error('Error fetching contributors:', error);
                })
                .finally(() => {
                    this.loading = false;
                    console.log('Loading finished');
                });
        }
    }
};


// Highlight the active nav link based on the section in view
document.addEventListener('DOMContentLoaded', function() {
    const navLinks = document.querySelectorAll('.nav-link');
    const sections = document.querySelectorAll('.card[id]'); // Only select sections with an id

    const observerOptions = {
        root: null,
        rootMargin: '0px 0px -50% 0px', // Adjust the bottom margin to trigger earlier
        threshold: 0.1 // Lower threshold to detect smaller sections
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            console.log(entry.target.id + ' is intersecting: ' + entry.isIntersecting);
            if (entry.isIntersecting) {
                navLinks.forEach(link => link.classList.remove('active'));
                const activeLink = document.querySelector(`.nav-link[href="#${entry.target.id}"]`);
                if (activeLink) {
                    activeLink.classList.add('active');
                    console.log(activeLink + ' is active');
                }
            }
        });
    }, observerOptions);

    sections.forEach(section => {
        console.log('Observing section:', section.id);
        observer.observe(section);
    });

    navLinks.forEach(link => {
        link.addEventListener('click', function(event) {
            // Allow default behavior for the login link
            if (this.getAttribute('href') === '<?= route_to('login') ?>') {
                return;
            }

            event.preventDefault();
            const targetId = this.getAttribute('href').substring(1);
            const targetElement = document.getElementById(targetId);
            const offsetPosition = targetElement.getBoundingClientRect().top + window.pageYOffset - 70; // Adjust this value based on your navbar height

            window.scrollTo({
                top: offsetPosition,
                behavior: 'smooth'
            });

            navLinks.forEach(nav => nav.classList.remove('active'));
            this.classList.add('active');
        });
    });

    // Highlight the correct nav item on page load based on the URL hash
    const currentHash = window.location.hash;
    if (currentHash) {
        const activeLink = document.querySelector(`.nav-link[href="${currentHash}"]`);
        if (activeLink) {
            navLinks.forEach(nav => nav.classList.remove('active'));
            activeLink.classList.add('active');
            console.log(activeLink + ' is active on load');
        }
    }
});
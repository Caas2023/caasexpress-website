/**
 * Caas Express - Interactive JavaScript
 * Modern animations, scroll effects, and form handling
 */

// Wait for DOM to be ready
document.addEventListener('DOMContentLoaded', () => {
  initMobileMenu();
  initStickyHeader();
  initScrollAnimations();
  initCounterAnimations();
  initSmoothScroll();
  initContactForm();
  initActiveNavLinks();
  initPhoneMask(); 
  initParallax();  
  initBlockRightClick(); // Função que bloqueia o botão direito
});

/**
 * Bloqueia o clique com o botão direito do mouse
 */
function initBlockRightClick() {
  document.addEventListener('contextmenu', function(e) {
    e.preventDefault();
  });
}

/**
 * Mobile Menu Toggle
 */
function initMobileMenu() {
  const menuToggle = document.getElementById('menu-toggle');
  const navMenu = document.getElementById('nav-menu');
  const navCta = document.getElementById('nav-cta');
  
  if (!menuToggle) return;
  
  menuToggle.addEventListener('click', () => {
    menuToggle.classList.toggle('active');
    navMenu.classList.toggle('active');
    navCta.classList.toggle('active');
  });
  
  // Close menu when clicking a link
  document.querySelectorAll('.nav-link').forEach(link => {
    link.addEventListener('click', () => {
      menuToggle.classList.remove('active');
      navMenu.classList.remove('active');
      navCta.classList.remove('active');
    });
  });
}

/**
 * Sticky Header on Scroll
 */
function initStickyHeader() {
  const header = document.getElementById('header');
  if (!header) return;
  
  let lastScroll = 0;
  
  window.addEventListener('scroll', () => {
    const currentScroll = window.pageYOffset;
    
    if (currentScroll > 50) {
      header.classList.add('scrolled');
    } else {
      header.classList.remove('scrolled');
    }
    
    lastScroll = currentScroll;
  });
}

/**
 * Scroll Animations using Intersection Observer
 */
function initScrollAnimations() {
  const observerOptions = {
    root: null,
    rootMargin: '0px',
    threshold: 0.1
  };
  
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('animated');
        // Unobserve after animation to prevent re-triggering
        observer.unobserve(entry.target);
      }
    });
  }, observerOptions);
  
  // Observe all elements with animate-on-scroll class
  document.querySelectorAll('.animate-on-scroll').forEach(el => {
    observer.observe(el);
  });
}

/**
 * Counter Animation for Stats Section
 */
function initCounterAnimations() {
  const counters = document.querySelectorAll('.stat-number[data-target]');
  
  const observerOptions = {
    threshold: 0.5
  };
  
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        animateCounter(entry.target);
        observer.unobserve(entry.target);
      }
    });
  }, observerOptions);
  
  counters.forEach(counter => observer.observe(counter));
}

function animateCounter(element) {
  const target = parseInt(element.getAttribute('data-target'));
  const duration = 2000; // 2 seconds
  const start = 0;
  const startTime = performance.now();
  
  // Get the suffix (+ or %)
  const suffix = element.innerHTML.includes('%') ? '%' : '+';
  
  function updateCounter(currentTime) {
    const elapsed = currentTime - startTime;
    const progress = Math.min(elapsed / duration, 1);
    
    // Easing function for smooth animation
    const easeOutQuart = 1 - Math.pow(1 - progress, 4);
    const current = Math.floor(start + (target - start) * easeOutQuart);
    
    // Format large numbers
    let displayValue = current;
    if (target >= 10000) {
      displayValue = (current / 1000).toFixed(0) + 'k';
    }
    
    element.innerHTML = `${displayValue}<span>${suffix}</span>`;
    
    if (progress < 1) {
      requestAnimationFrame(updateCounter);
    }
  }
  
  requestAnimationFrame(updateCounter);
}

/**
 * Smooth Scroll for Navigation Links
 */
function initSmoothScroll() {
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
      const href = this.getAttribute('href');
      if (href === '#') return;
      
      e.preventDefault();
      
      const target = document.querySelector(href);
      if (target) {
        const headerOffset = 80;
        const elementPosition = target.getBoundingClientRect().top;
        const offsetPosition = elementPosition + window.pageYOffset - headerOffset;
        
        window.scrollTo({
          top: offsetPosition,
          behavior: 'smooth'
        });
      }
    });
  });
}

/**
 * Update Active Nav Link on Scroll
 */
function initActiveNavLinks() {
  const sections = document.querySelectorAll('section[id]');
  const navLinks = document.querySelectorAll('.nav-link');
  
  function updateActiveLink() {
    const scrollPosition = window.scrollY + 100;
    
    sections.forEach(section => {
      const sectionTop = section.offsetTop;
      const sectionHeight = section.offsetHeight;
      const sectionId = section.getAttribute('id');
      
      if (scrollPosition >= sectionTop && scrollPosition < sectionTop + sectionHeight) {
        navLinks.forEach(link => {
          link.classList.remove('active');
          if (link.getAttribute('href') === `#${sectionId}`) {
            link.classList.add('active');
          }
        });
      }
    });
  }
  
  window.addEventListener('scroll', updateActiveLink);
  updateActiveLink(); // Initial call
}

/**
 * Contact Form Handling
 */
function initContactForm() {
  const form = document.getElementById('contact-form');
  if (!form) return;
  
  form.addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Get form data
    const formData = new FormData(form);
    const name = formData.get('name');
    const company = formData.get('company');
    const pickup = formData.get('pickup');
    const delivery = formData.get('delivery');
    const cargo = formData.get('cargo');
    const notes = formData.get('notes');
    const serviceType = formData.get('service'); 
    
    // Build WhatsApp message
    let whatsappMessage = `Olá! Gostaria de solicitar um orçamento de entrega:\n\n`;
    if (name) whatsappMessage += `*Nome:* ${name}\n`;
    if (company) whatsappMessage += `*Empresa:* ${company}\n`;
    if (serviceType) whatsappMessage += `*Serviço:* ${getServiceName(serviceType)}\n`;
    if (pickup) whatsappMessage += `*Coleta:* ${pickup}\n`;
    if (delivery) whatsappMessage += `*Entrega:* ${delivery}\n`;
    if (cargo) whatsappMessage += `*Carga:* ${cargo}\n`;
    if (notes) whatsappMessage += `*Observação:* ${notes}\n`;
    
    // Encode message for URL
    const encodedMessage = encodeURIComponent(whatsappMessage);
    
    // Open WhatsApp
    window.open(`https://wa.me/5511957248425?text=${encodedMessage}`, '_blank');
    
    // Show success feedback
    showFormSuccess();
    
    // Reset form
    form.reset();
  });
}

function getServiceName(value) {
  const services = {
    'expressa': 'Entrega Expressa',
    'agendada': 'Entrega Agendada',
    'urgente': 'Entrega Urgente',
    'outro': 'Outro'
  };
  return services[value] || value;
}

function showFormSuccess() {
  const btn = document.querySelector('#contact-form button[type="submit"]') || document.querySelector('.contact-form .btn');
  if (!btn) return;

  const originalText = btn.innerHTML;
  
  btn.innerHTML = `
    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
      <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
    </svg>
    Enviado com Sucesso!
  `;
  btn.style.background = '#25D366';
  
  setTimeout(() => {
    btn.innerHTML = originalText;
    btn.style.background = '';
  }, 3000);
}

/**
 * Phone Input Mask (Brazilian Format)
 */
function initPhoneMask() {
  const phoneInput = document.getElementById('phone');
  if (phoneInput) {
    phoneInput.addEventListener('input', function(e) {
      let value = e.target.value.replace(/\D/g, '');
      
      if (value.length > 11) value = value.slice(0, 11);
      
      if (value.length > 6) {
        value = `(${value.slice(0, 2)}) ${value.slice(2, 7)}-${value.slice(7)}`;
      } else if (value.length > 2) {
        value = `(${value.slice(0, 2)}) ${value.slice(2)}`;
      } else if (value.length > 0) {
        value = `(${value}`;
      }
      
      e.target.value = value;
    });
  }
}

/**
 * Parallax Effect for Hero Section (Desktop Only)
 */
function initParallax() {
  if (window.innerWidth > 768) {
    window.addEventListener('scroll', () => {
      const hero = document.querySelector('.hero');
      if (hero) {
        const scrolled = window.pageYOffset;
        hero.style.backgroundPositionY = `${scrolled * 0.3}px`;
      }
    });
  }
}

/**
 * Add loading state to page
 */
window.addEventListener('load', () => {
  document.body.classList.add('loaded');
});

document.addEventListener('DOMContentLoaded', function() {
    // Handle collapsible sections
    const collapsibles = document.querySelectorAll('.collapsible-header');
    
    collapsibles.forEach(header => {
      header.addEventListener('click', function() {
        this.classList.toggle('active');
        
        const content = this.nextElementSibling;
        const toggleIcon = this.querySelector('.toggle-icon');
        
        if (content.style.maxHeight) {
          content.style.maxHeight = null;
          toggleIcon.textContent = '+';
        } else {
          content.style.maxHeight = content.scrollHeight + 'px';
          toggleIcon.textContent = '-';
        }
      });
    });
    
    // Handle video expansion
    const videoContainers = document.querySelectorAll('.video-container');
    
    videoContainers.forEach(container => {
      container.addEventListener('click', function() {
        this.classList.toggle('expanded');
        
        // Toggle video controls visibility
        const video = this.querySelector('video');
        if (this.classList.contains('expanded')) {
          video.controls = true;
          
          // Scroll to the expanded video
          window.scrollTo({
            top: this.offsetTop - 20,
            behavior: 'smooth'
          });
        } else {
          // Small delay before removing controls when collapsing
          setTimeout(() => {
            if (!this.classList.contains('expanded')) {
              video.controls = false;
            }
          }, 300);
        }
      });
    });
    
    // Initialize videos with no controls until expanded
    document.querySelectorAll('.lesson-video').forEach(video => {
      video.controls = false;
    });
  });
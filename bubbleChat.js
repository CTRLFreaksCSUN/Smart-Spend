document.addEventListener("DOMContentLoaded", () => {
  // Ensure the container has the correct ID in your HTML!
  const chatContainer = document.getElementById("chatContainer");
  const chatBubble = document.getElementById("chatBubble");
  const chatPopup = document.getElementById("chatPopup");
  const closeChat = document.getElementById("closeChat");
  const iframe = document.querySelector(".chat-content iframe");
  const chatHeader = document.querySelector(".chat-header");

  // Create resize handle
  const resizeHandle = document.createElement("div");
  resizeHandle.className = "resize-handle";
  chatPopup.appendChild(resizeHandle);

  // Store initial dimensions and states
  let isResizing = false;
  let isDraggingWindow = false;
  let isDraggingBubble = false;
  let startX, startY, initialLeft, initialTop, initialWidth, initialHeight;

  // Make bubble draggable
  chatBubble.addEventListener("mousedown", startBubbleDrag);
  chatBubble.addEventListener("touchstart", startBubbleDrag, { passive: false });

  function startBubbleDrag(e) {
    e.preventDefault();
    e.stopPropagation();
    isDraggingBubble = true;
    
    // Get initial positions
    if (e.type === 'mousedown') {
      startX = e.clientX;
      startY = e.clientY;
    } else {
      startX = e.touches[0].clientX;
      startY = e.touches[0].clientY;
    }
    
    initialLeft = chatContainer.getBoundingClientRect().left;
    // Corrected from getBubbleClientRect() to getBoundingClientRect()
    initialTop = chatContainer.getBoundingClientRect().top;
    
    // Add active class for visual feedback
    chatBubble.classList.add("dragging");
    
    // Add move and end event listeners
    document.addEventListener("mousemove", dragBubble);
    document.addEventListener("touchmove", dragBubble, { passive: false });
    document.addEventListener("mouseup", endBubbleDrag);
    document.addEventListener("touchend", endBubbleDrag);
  }

  function dragBubble(e) {
    if (!isDraggingBubble) return;
    e.preventDefault();
    
    let clientX, clientY;
    if (e.type === 'mousemove') {
      clientX = e.clientX;
      clientY = e.clientY;
    } else {
      clientX = e.touches[0].clientX;
      clientY = e.touches[0].clientY;
    }
    
    // Calculate new position
    const newLeft = initialLeft + (clientX - startX);
    const newTop = initialTop + (clientY - startY);
    
    // Apply new position
    chatContainer.style.left = `${newLeft}px`;
    chatContainer.style.top = `${newTop}px`;
    chatContainer.style.right = "auto";
    chatContainer.style.bottom = "auto";
  }

  function endBubbleDrag() {
    isDraggingBubble = false;
    chatBubble.classList.remove("dragging");
    
    // Remove event listeners
    document.removeEventListener("mousemove", dragBubble);
    document.removeEventListener("touchmove", dragBubble);
    document.removeEventListener("mouseup", endBubbleDrag);
    document.removeEventListener("touchend", endBubbleDrag);
    
    // Snap to edges if near
    const rect = chatContainer.getBoundingClientRect();
    const windowWidth = window.innerWidth;
    const windowHeight = window.innerHeight;
    
    if (rect.right > windowWidth - 20) {
      chatContainer.style.left = "auto";
      chatContainer.style.right = "20px";
    }
    
    if (rect.bottom > windowHeight - 20) {
      chatContainer.style.top = "auto";
      chatContainer.style.bottom = "20px";
    }
  }

  // Toggle popup on bubble click (with check for dragging)
  chatBubble.addEventListener("click", (e) => {
    if (isDraggingBubble) {
      isDraggingBubble = false;
      return;
    }
    e.stopPropagation();
    chatPopup.classList.toggle("show");
    
    if (chatPopup.classList.contains("show")) {
      setTimeout(() => {
        iframe.src = "chatbox.php";
      }, 300);
    }
  });

  // Window dragging via header
  chatHeader.addEventListener("mousedown", startWindowDrag);

  function startWindowDrag(e) {
    if (e.target !== chatHeader && !e.target.closest('.chat-header')) return;
    
    e.preventDefault();
    isDraggingWindow = true;
    
    startX = e.clientX;
    startY = e.clientY;
    
    initialLeft = chatContainer.getBoundingClientRect().left;
    initialTop = chatContainer.getBoundingClientRect().top;
    
    document.addEventListener("mousemove", dragWindow);
    document.addEventListener("mouseup", endWindowDrag);
    chatHeader.classList.add("dragging");
  }

  function dragWindow(e) {
    if (isResizing) return;
    if (!isDraggingWindow) return;
    e.preventDefault();
    
    const newLeft = initialLeft + (e.clientX - startX);
    const newTop = initialTop + (e.clientY - startY);
    
    chatContainer.style.left = `${newLeft}px`;
    chatContainer.style.top = `${newTop}px`;
  }
  
  function endWindowDrag() {
    isDraggingWindow = false;
    document.removeEventListener("mousemove", dragWindow);
    document.removeEventListener("mouseup", endWindowDrag);
    chatHeader.classList.remove("dragging");
  }

  // Resize functionality
  resizeHandle.addEventListener("mousedown", initResize);
  
  function initResize(e) {
    e.preventDefault();
    e.stopPropagation();
    isResizing = true;
    
    startX = e.clientX;
    startY = e.clientY;
    initialWidth = parseInt(document.defaultView.getComputedStyle(chatPopup).width, 10);
    initialHeight = parseInt(document.defaultView.getComputedStyle(chatPopup).height, 10);
    
    document.addEventListener("mousemove", resize);
    document.addEventListener("mouseup", stopResize);
  }
  
  function resize(e) {
    if (!isResizing) return;
    
    const newWidth = initialWidth + (e.clientX - startX);
    const newHeight = initialHeight + (e.clientY - startY);
    
    const minWidth = 300;
    const minHeight = 400;
    const maxWidth = window.innerWidth - 40;
    const maxHeight = window.innerHeight - 40;
    
    if (newWidth > minWidth && newWidth < maxWidth) {
      chatPopup.style.width = `${newWidth}px`;
    }
    
    if (newHeight > minHeight && newHeight < maxHeight) {
      chatPopup.style.height = `${newHeight}px`;
    }
  }
  
  function stopResize() {
    isResizing = false;
    document.removeEventListener("mousemove", resize);
    document.removeEventListener("mouseup", stopResize);
  }

  // Close popup when clicking the close button
  closeChat.addEventListener("click", (e) => {
    e.stopPropagation();
    chatPopup.classList.remove("show");
  });

  document.addEventListener("click", (e) => {
    if (!chatPopup.contains(e.target) && e.target !== chatBubble) {
      chatPopup.classList.remove("show");
    }
  });

  // Prevent default dragstart behavior on the container
  chatContainer.ondragstart = function() {
    return false;
  };
});

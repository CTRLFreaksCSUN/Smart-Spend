document.addEventListener("DOMContentLoaded", () => {
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

  // Set question mark as bubble content
  chatBubble.textContent = "?"; // This replaces any existing content

  // State variables
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
    
    const clientX = e.clientX || e.touches[0].clientX;
    const clientY = e.clientY || e.touches[0].clientY;
    
    const rect = chatContainer.getBoundingClientRect();
    startX = clientX;
    startY = clientY;
    initialLeft = rect.left;
    initialTop = rect.top;
    
    chatBubble.classList.add("dragging");
    document.addEventListener("mousemove", dragBubble);
    document.addEventListener("touchmove", dragBubble, { passive: false });
    document.addEventListener("mouseup", endBubbleDrag);
    document.addEventListener("touchend", endBubbleDrag);
  }

  function dragBubble(e) {
    if (!isDraggingBubble) return;
    e.preventDefault();
    
    const clientX = e.clientX || e.touches[0].clientX;
    const clientY = e.clientY || e.touches[0].clientY;
    
    chatContainer.style.left = `${initialLeft + (clientX - startX)}px`;
    chatContainer.style.top = `${initialTop + (clientY - startY)}px`;
    chatContainer.style.right = "auto";
    chatContainer.style.bottom = "auto";
  }

  function endBubbleDrag() {
    if (!isDraggingBubble) return;
    isDraggingBubble = false;
    chatBubble.classList.remove("dragging");
    
    document.removeEventListener("mousemove", dragBubble);
    document.removeEventListener("touchmove", dragBubble);
    document.removeEventListener("mouseup", endBubbleDrag);
    document.removeEventListener("touchend", endBubbleDrag);
    
    // Snap to edges
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

  // Toggle popup
  chatBubble.addEventListener("click", (e) => {
    if (isDraggingBubble) {
      isDraggingBubble = false;
      return;
    }
    e.stopPropagation();
    chatPopup.classList.toggle("show");
    
    if (chatPopup.classList.contains("show") && !iframe.src) {
      iframe.src = "chatbox.php";
    }
  });

  // Window dragging
  chatHeader.addEventListener("mousedown", startWindowDrag);

  function startWindowDrag(e) {
    if (!e.target.closest('.chat-header')) return;
    e.preventDefault();
    isDraggingWindow = true;
    
    startX = e.clientX;
    startY = e.clientY;
    initialLeft = chatContainer.getBoundingClientRect().left;
    initialTop = chatContainer.getBoundingClientRect().top;
    
    document.addEventListener("mousemove", dragWindow);
    document.addEventListener("mouseup", endWindowDrag);
  }

  function dragWindow(e) {
    if (!isDraggingWindow || isResizing) return;
    e.preventDefault();
    
    chatContainer.style.left = `${initialLeft + (e.clientX - startX)}px`;
    chatContainer.style.top = `${initialTop + (e.clientY - startY)}px`;
  }
  
  function endWindowDrag() {
    isDraggingWindow = false;
    document.removeEventListener("mousemove", dragWindow);
    document.removeEventListener("mouseup", endWindowDrag);
  }

  // Resize functionality
  resizeHandle.addEventListener("mousedown", initResize);
  
  function initResize(e) {
    e.preventDefault();
    e.stopPropagation();
    isResizing = true;
    
    startX = e.clientX;
    startY = e.clientY;
    initialWidth = parseInt(getComputedStyle(chatPopup).width, 10);
    initialHeight = parseInt(getComputedStyle(chatPopup).height, 10);
    
    document.addEventListener("mousemove", resize);
    document.addEventListener("mouseup", stopResize);
  }
  
  function resize(e) {
    if (!isResizing) return;
    
    const newWidth = Math.min(
      Math.max(300, initialWidth + (e.clientX - startX)),
      window.innerWidth - 40
    );
    const newHeight = Math.min(
      Math.max(400, initialHeight + (e.clientY - startY)),
      window.innerHeight - 40
    );
    
    chatPopup.style.width = `${newWidth}px`;
    chatPopup.style.height = `${newHeight}px`;
  }
  
  function stopResize() {
    isResizing = false;
    document.removeEventListener("mousemove", resize);
    document.removeEventListener("mouseup", stopResize);
  }

  // Close handlers
  closeChat.addEventListener("click", (e) => {
    e.stopPropagation();
    chatPopup.classList.remove("show");
  });

  document.addEventListener("click", (e) => {
    if (!chatPopup.contains(e.target) && e.target !== chatBubble) {
      chatPopup.classList.remove("show");
    }
  });
});

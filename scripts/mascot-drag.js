// 章魚使用 Pointer Events，讓手機與滑鼠共用拖曳邏輯。
(() => {
  const mascot = document.getElementById('mascot-helper');
  if (!mascot) return;
  const image = mascot.querySelector('img');
  const bubble = mascot.querySelector('.chat-bubble');
  image.draggable = false;
  let gesture = null;
  let suppressClick = false;
  const clamp = (value, min, max) => Math.max(min, Math.min(value, Math.max(min, max)));
  function bounds() {
    const view = window.visualViewport;
    return { x: view?.offsetLeft || 0, y: view?.offsetTop || 0,
      width: view?.width || innerWidth, height: view?.height || innerHeight };
  }
  function positionBubble() {
    if (!bubble || getComputedStyle(bubble).display === 'none') return;
    const view = bounds();
    const rect = mascot.getBoundingClientRect();
    bubble.style.maxHeight = `${Math.max(80, view.height - 24)}px`;
    const size = bubble.getBoundingClientRect();
    const above = rect.top - size.height - 12;
    const top = above >= view.y + 12 ? above : rect.bottom + 12;
    bubble.style.left = `${clamp(rect.right - size.width, view.x + 12, view.x + view.width - size.width - 12)}px`;
    bubble.style.top = `${clamp(top, view.y + 12, view.y + view.height - size.height - 12)}px`;
  }
  function place(left, top) {
    const view = bounds();
    const rect = mascot.getBoundingClientRect();
    mascot.style.left = `${clamp(left, view.x + 8, view.x + view.width - rect.width - 8)}px`;
    mascot.style.top = `${clamp(top, view.y + 8, view.y + view.height - rect.height - 8)}px`;
    mascot.style.right = 'auto';
    mascot.style.bottom = 'auto';
    positionBubble();
  }
  mascot.addEventListener('pointerdown', event => {
    if (!event.isPrimary || event.button !== 0 || event.target.closest('.chat-bubble')) return;
    const rect = mascot.getBoundingClientRect();
    gesture = { id: event.pointerId, x: event.clientX, y: event.clientY,
      left: rect.left, top: rect.top, moved: false };
    suppressClick = false;
    mascot.setPointerCapture(event.pointerId);
  });
  mascot.addEventListener('pointermove', event => {
    if (!gesture || gesture.id !== event.pointerId) return;
    const dx = event.clientX - gesture.x, dy = event.clientY - gesture.y;
    if (!gesture.moved && Math.hypot(dx, dy) < 8) return;
    gesture.moved = true;
    mascot.classList.add('is-dragging');
    place(gesture.left + dx, gesture.top + dy);
  });
  function endDrag(event) {
    if (!gesture || gesture.id !== event.pointerId) return;
    suppressClick = gesture.moved;
    gesture = null;
    mascot.classList.remove('is-dragging');
    if (mascot.hasPointerCapture(event.pointerId)) mascot.releasePointerCapture(event.pointerId);
  }
  mascot.addEventListener('pointerup', endDrag);
  mascot.addEventListener('pointercancel', endDrag);
  mascot.addEventListener('lostpointercapture', endDrag);
  mascot.addEventListener('click', event => {
    if (suppressClick && !event.target.closest('.chat-bubble')) {
      suppressClick = false;
      event.preventDefault();
      event.stopImmediatePropagation();
      return;
    }
    // 等漫畫選單的點擊事件完成，再依實際高度定位。
    queueMicrotask(positionBubble);
  }, true);
  function fitViewport() {
    const rect = mascot.getBoundingClientRect();
    place(rect.left, rect.top);
  }
  window.addEventListener('resize', fitViewport);
  window.visualViewport?.addEventListener('resize', fitViewport);
  window.visualViewport?.addEventListener('scroll', fitViewport);
  if (bubble) new ResizeObserver(positionBubble).observe(bubble);
  if (image.complete) fitViewport();
  else image.addEventListener('load', fitViewport, { once: true });
})();

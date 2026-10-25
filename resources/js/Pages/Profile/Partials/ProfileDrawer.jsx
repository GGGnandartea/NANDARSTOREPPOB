import React, { useEffect, useRef } from 'react';
import { Icon } from './ProfileIcons';

export default function ProfileDrawer({ open, onClose, children }) {
  const panelRef = useRef(null);

  useEffect(() => {
    if (!open) return;
    const trigger = document.activeElement;
    const scrollY = window.scrollY;
    const body = document.body;
    const previous = { position: body.style.position, top: body.style.top, width: body.style.width, overflow: body.style.overflow };
    Object.assign(body.style, { position: 'fixed', top: `-${scrollY}px`, width: '100%', overflow: 'hidden' });
    panelRef.current?.querySelector('button')?.focus({ preventScroll: true });

    const handleKey = (event) => {
      if (event.key === 'Escape') onClose();
      if (event.key !== 'Tab') return;
      const elements = [...panelRef.current.querySelectorAll('button:not(:disabled), a[href], input:not(:disabled), [tabindex="0"]')]
        .filter((element) => element.getClientRects().length > 0);
      const first = elements[0];
      const last = elements.at(-1);
      if (event.shiftKey && (document.activeElement === first || !panelRef.current.contains(document.activeElement))) {
        event.preventDefault();
        last?.focus();
      } else if (!event.shiftKey && (document.activeElement === last || !panelRef.current.contains(document.activeElement))) {
        event.preventDefault();
        first?.focus();
      }
    };
    const desktop = window.matchMedia('(min-width: 768px)');
    const closeOnDesktop = () => { if (desktop.matches) onClose(); };
    document.addEventListener('keydown', handleKey);
    desktop.addEventListener('change', closeOnDesktop);
    closeOnDesktop();
    return () => {
      document.removeEventListener('keydown', handleKey);
      desktop.removeEventListener('change', closeOnDesktop);
      Object.assign(body.style, previous);
      window.scrollTo({ top: scrollY, behavior: 'instant' });
      if (trigger?.isConnected) trigger.focus({ preventScroll: true });
    };
  }, [open, onClose]);

  return (
    <div className={`profile-drawer fixed inset-0 z-50 md:hidden ${open ? 'is-open' : ''}`} inert={!open} aria-hidden={!open}>
      <div className="profile-drawer-backdrop absolute inset-0 bg-black/50" onClick={onClose} aria-hidden="true" />
      <section
        id="profile-mobile-menu"
        ref={panelRef}
        role="dialog"
        aria-modal={open ? 'true' : undefined}
        aria-labelledby="profile-mobile-menu-title"
        className="profile-drawer-panel relative w-72 max-w-[85vw] h-full bg-white dark:bg-gray-900 shadow-2xl overflow-y-auto overscroll-contain"
      >
        <div className="sticky top-0 z-10 flex items-center justify-between px-4 py-3 bg-white dark:bg-gray-900 border-b border-gray-100 dark:border-gray-700">
          <span id="profile-mobile-menu-title" className="font-black text-gray-900 dark:text-white">Menu</span>
          <button type="button" onClick={onClose} className="min-h-11 min-w-11 flex items-center justify-center rounded-xl text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors" aria-label="Tutup Sidebar">
            <Icon.X className="w-5 h-5" />
          </button>
        </div>
        {children}
      </section>
    </div>
  );
}

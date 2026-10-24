/**
 * @file
 * Progressive enhancement for the main navigation.
 *
 * - Narrow screens: a Menu button shows and hides the whole navigation.
 * - Wide screens: section buttons open one submenu at a time.
 * - Escape closes the open submenu and returns focus to its button.
 * Without JavaScript all links remain visible.
 */
((Drupal, once) => {
  const wide = window.matchMedia('(min-width: 64em)');

  function setOpen(toggle, open) {
    const sub = document.getElementById(toggle.getAttribute('aria-controls'));
    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    if (sub) {
      sub.hidden = !open;
    }
  }

  Drupal.behaviors.mofaNavigation = {
    attach(context) {
      once('mofa-nav', '#site-nav', context).forEach((nav) => {
        const menuToggle = document.querySelector('.site-header__menu-toggle');
        const toggles = Array.from(nav.querySelectorAll('[data-nav-toggle]'));

        toggles.forEach((toggle) => {
          toggle.hidden = false;
          setOpen(toggle, false);
          toggle.addEventListener('click', () => {
            const open = toggle.getAttribute('aria-expanded') !== 'true';
            if (wide.matches) {
              toggles.forEach((other) => other !== toggle && setOpen(other, false));
            }
            setOpen(toggle, open);
          });
        });

        nav.addEventListener('keydown', (event) => {
          if (event.key !== 'Escape') {
            return;
          }
          const openToggle = toggles.find((t) => t.getAttribute('aria-expanded') === 'true');
          if (openToggle) {
            setOpen(openToggle, false);
            openToggle.focus();
          }
        });

        document.addEventListener('click', (event) => {
          if (wide.matches && !nav.contains(event.target)) {
            toggles.forEach((t) => setOpen(t, false));
          }
        });

        if (menuToggle) {
          menuToggle.hidden = false;
          const sync = () => {
            if (wide.matches) {
              nav.classList.remove('is-collapsed');
              menuToggle.setAttribute('aria-expanded', 'true');
            }
            else {
              const open = menuToggle.getAttribute('aria-expanded') === 'true' && nav.dataset.userOpened === 'true';
              nav.classList.toggle('is-collapsed', !open);
              menuToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            }
          };
          menuToggle.addEventListener('click', () => {
            const open = menuToggle.getAttribute('aria-expanded') !== 'true';
            nav.dataset.userOpened = open ? 'true' : 'false';
            menuToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            nav.classList.toggle('is-collapsed', !open);
          });
          wide.addEventListener('change', sync);
          sync();
        }
      });
    },
  };
})(Drupal, once);

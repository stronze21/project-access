(() => {
    const userAgent = navigator.userAgent || '';
    const reportedPlatform = navigator.userAgentData?.platform || navigator.platform || '';
    const isIPadOs = /Mac/i.test(reportedPlatform) && navigator.maxTouchPoints > 1;
    let platform = 'unknown';

    if (/iPhone|iPad|iPod/i.test(userAgent) || isIPadOs) {
        platform = 'ios';
    } else if (/Android/i.test(userAgent)) {
        platform = 'android';
    } else if (/Mac/i.test(reportedPlatform)) {
        platform = 'macos';
    } else if (/Win/i.test(reportedPlatform)) {
        platform = 'windows';
    } else if (/Linux/i.test(reportedPlatform)) {
        platform = 'linux';
    }

    const isTablet = platform === 'ios'
        ? /iPad/i.test(userAgent) || isIPadOs
        : platform === 'android' && !/Mobile/i.test(userAgent);
    const isMobile = /Mobile|iPhone|iPod|Android/i.test(userAgent) && !isTablet;
    const deviceType = isTablet ? 'tablet' : (isMobile ? 'mobile' : 'desktop');
    const isStandalone = window.matchMedia('(display-mode: standalone)').matches
        || window.navigator.standalone === true;
    const device = Object.freeze({ platform, deviceType, isStandalone });

    window.ProjectAccessDevice = device;
    document.documentElement.dataset.devicePlatform = platform;
    document.documentElement.dataset.deviceType = deviceType;
    window.dispatchEvent(new CustomEvent('project-access-device-detected', { detail: device }));
})();

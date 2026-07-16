<script>
    (() => {
        const panel = document.querySelector('.editor-panel');
        if (!panel) return;

        const elements = [...document.querySelectorAll('.editable-element[data-editor-key]')];
        const targetSelect = document.getElementById('editor-target');
        const metricInputs = {
            x: document.getElementById('editor-x'),
            y: document.getElementById('editor-y'),
            width: document.getElementById('editor-width'),
            height: document.getElementById('editor-height'),
            fontSize: document.getElementById('editor-font-size'),
        };
        const storageKey = `access-id-card-editor:${panel.dataset.residentId}`;
        const originalStyles = new Map(elements.map((element) => [element.dataset.editorKey, element.getAttribute('style')]));
        const transforms = new Map(elements.map((element) => [element.dataset.editorKey, {
            rotation: 0,
            flipX: 1,
            flipY: 1,
        }]));
        let selected = null;
        let dragState = null;

        const inches = (pixels) => pixels / 96;
        const pixels = (inchesValue) => inchesValue * 96;
        const numberValue = (input, fallback = 0) => Number.isFinite(Number.parseFloat(input.value))
            ? Number.parseFloat(input.value)
            : fallback;

        const savedState = (() => {
            try {
                return JSON.parse(localStorage.getItem(storageKey) || '{}');
            } catch (error) {
                return {};
            }
        })();

        elements.forEach((element) => {
            const saved = savedState[element.dataset.editorKey];
            if (!saved) return;

            if (typeof saved.style === 'string') element.setAttribute('style', saved.style);
            if (saved.transform) transforms.set(element.dataset.editorKey, saved.transform);
        });

        const saveState = () => {
            const state = {};
            elements.forEach((element) => {
                state[element.dataset.editorKey] = {
                    style: element.getAttribute('style') || '',
                    transform: transforms.get(element.dataset.editorKey),
                };
            });
            localStorage.setItem(storageKey, JSON.stringify(state));
        };

        const refreshInputs = () => {
            if (!selected) return;

            metricInputs.x.value = inches(selected.offsetLeft).toFixed(3);
            metricInputs.y.value = inches(selected.offsetTop).toFixed(3);
            metricInputs.width.value = inches(selected.offsetWidth).toFixed(3);
            metricInputs.height.value = inches(selected.offsetHeight).toFixed(3);

            const isText = selected.classList.contains('field-value');
            metricInputs.fontSize.disabled = !isText;
            metricInputs.fontSize.value = isText ? Number.parseFloat(getComputedStyle(selected).fontSize).toFixed(1) : '';
        };

        const applyTransform = () => {
            if (!selected) return;
            const transform = transforms.get(selected.dataset.editorKey);
            selected.style.transform = `rotate(${transform.rotation}deg) scale(${transform.flipX}, ${transform.flipY})`;
        };

        const selectElement = (key) => {
            selected?.classList.remove('editor-selected');
            selected = elements.find((element) => element.dataset.editorKey === key) || null;
            selected?.classList.add('editor-selected');
            document.body.classList.toggle('editor-mode', Boolean(selected));
            refreshInputs();
        };

        elements.forEach((element) => {
            const option = document.createElement('option');
            option.value = element.dataset.editorKey;
            option.textContent = element.dataset.editorLabel;
            targetSelect.appendChild(option);
        });

        targetSelect.addEventListener('change', () => selectElement(targetSelect.value));

        const applyMetrics = () => {
            if (!selected) return;

            selected.style.left = `${numberValue(metricInputs.x, inches(selected.offsetLeft))}in`;
            selected.style.top = `${numberValue(metricInputs.y, inches(selected.offsetTop))}in`;
            selected.style.width = `${Math.max(.05, numberValue(metricInputs.width, inches(selected.offsetWidth)))}in`;
            selected.style.height = `${Math.max(.03, numberValue(metricInputs.height, inches(selected.offsetHeight)))}in`;

            if (selected.classList.contains('field-value')) {
                selected.style.fontSize = `${Math.max(5, numberValue(metricInputs.fontSize, 10))}px`;
            }

            saveState();
            refreshInputs();
        };

        Object.values(metricInputs).forEach((input) => {
            input.addEventListener('change', applyMetrics);
        });

        panel.querySelectorAll('[data-move-x]').forEach((button) => {
            button.addEventListener('click', () => {
                if (!selected) return;
                selected.style.left = `${inches(selected.offsetLeft) + Number(button.dataset.moveX)}in`;
                selected.style.top = `${inches(selected.offsetTop) + Number(button.dataset.moveY)}in`;
                saveState();
                refreshInputs();
            });
        });

        panel.querySelectorAll('[data-action]').forEach((button) => {
            button.addEventListener('click', () => {
                const action = button.dataset.action;
                if (!selected && action !== 'reset-all') return;

                if (action === 'larger' || action === 'smaller') {
                    const direction = action === 'larger' ? 1 : -1;
                    if (selected.classList.contains('field-value')) {
                        const currentSize = Number.parseFloat(getComputedStyle(selected).fontSize);
                        selected.style.fontSize = `${Math.max(5, currentSize + direction)}px`;
                    } else {
                        const factor = action === 'larger' ? 1.05 : .95;
                        selected.style.width = `${Math.max(.05, inches(selected.offsetWidth) * factor)}in`;
                        selected.style.height = `${Math.max(.03, inches(selected.offsetHeight) * factor)}in`;
                    }
                }

                if (action === 'rotate-left' || action === 'rotate-right') {
                    const transform = transforms.get(selected.dataset.editorKey);
                    transform.rotation += action === 'rotate-right' ? 5 : -5;
                    applyTransform();
                }

                if (action === 'flip-horizontal' || action === 'flip-vertical') {
                    const transform = transforms.get(selected.dataset.editorKey);
                    if (action === 'flip-horizontal') transform.flipX *= -1;
                    if (action === 'flip-vertical') transform.flipY *= -1;
                    applyTransform();
                }

                if (action === 'toggle-fit' && selected instanceof HTMLImageElement) {
                    selected.style.objectFit = getComputedStyle(selected).objectFit === 'cover' ? 'contain' : 'cover';
                }

                if (action === 'reset-selected') {
                    const originalStyle = originalStyles.get(selected.dataset.editorKey);
                    if (originalStyle === null) selected.removeAttribute('style');
                    else selected.setAttribute('style', originalStyle);
                    transforms.set(selected.dataset.editorKey, { rotation: 0, flipX: 1, flipY: 1 });
                }

                if (action === 'reset-all') {
                    elements.forEach((element) => {
                        const originalStyle = originalStyles.get(element.dataset.editorKey);
                        if (originalStyle === null) element.removeAttribute('style');
                        else element.setAttribute('style', originalStyle);
                        transforms.set(element.dataset.editorKey, { rotation: 0, flipX: 1, flipY: 1 });
                    });
                    localStorage.removeItem(storageKey);
                } else {
                    saveState();
                }

                refreshInputs();
            });
        });

        elements.forEach((element) => {
            element.addEventListener('pointerdown', (event) => {
                if (element !== selected) return;
                event.preventDefault();
                dragState = {
                    startX: event.clientX,
                    startY: event.clientY,
                    left: element.offsetLeft,
                    top: element.offsetTop,
                };
                element.setPointerCapture?.(event.pointerId);
            });

            element.addEventListener('pointermove', (event) => {
                if (!dragState || element !== selected) return;
                selected.style.left = `${inches(dragState.left + event.clientX - dragState.startX)}in`;
                selected.style.top = `${inches(dragState.top + event.clientY - dragState.startY)}in`;
                refreshInputs();
            });

            element.addEventListener('pointerup', () => {
                if (!dragState) return;
                dragState = null;
                saveState();
            });
        });

        const collapseButton = panel.querySelector('.editor-collapse');
        collapseButton.addEventListener('click', () => {
            const collapsed = panel.classList.toggle('collapsed');
            collapseButton.textContent = collapsed ? '+' : '−';
            collapseButton.setAttribute('aria-expanded', String(!collapsed));
        });

        window.addEventListener('beforeprint', () => document.body.classList.remove('editor-mode'));
        window.addEventListener('afterprint', () => document.body.classList.add('editor-mode'));

        if (elements.length) {
            targetSelect.value = elements[0].dataset.editorKey;
            selectElement(targetSelect.value);
        }
    })();
</script>

/**
 * Registers the `majdataPreview` Alpine component: a lazy loader for the
 * MajdataEditor Unity WebGL chart preview shown beside the simai code editor
 * on the chart difficulty page.
 *
 * The build (public/WebGLBuild) is only fetched after the user opts in, so the
 * ~32 MB payload never loads on a plain page view. Once the instance is ready
 * the chart is handed to the build with a single `SendMessage` call carrying
 * the maidata/track/art/pv URLs and the level token - the same contract used by
 * the reference app in old/majdataonline.
 */
const BUILD_BASE = '/WebGLBuild';

const LOADER_URL = `${BUILD_BASE}/Build.loader.js`;
const DATA_URL = `${BUILD_BASE}/Build.data`;
const FRAMEWORK_URL = `${BUILD_BASE}/Build.framework.js`;
const CODE_URL = `${BUILD_BASE}/Build.wasm`;

const GAME_OBJECT = 'HandleJSMessages';
const RECEIVE_METHOD = 'ReceiveMessage';

/**
 * Delay before handing the chart to the freshly created instance, mirroring the
 * reference app: the scene's message handler is not ready the instant the
 * instance resolves.
 */
const MESSAGE_DELAY = 500;

/** Unity build files are fetched from relative URLs, so requests must be same-origin. */
const supported = typeof window !== 'undefined' && window.WebAssembly !== undefined;

/** @type {Promise<void> | null} */
let loaderPromise = null;

/** Shared across components: one Unity instance per page. */
/** @type {any} */
let unityInstance = null;

/**
 * Inject the Unity loader script once and resolve when `createUnityInstance` is available.
 */
function loadUnityLoader() {
    if (window.createUnityInstance) {
        return Promise.resolve();
    }

    loaderPromise ??= new Promise((resolve, reject) => {
        const script = document.createElement('script');

        script.src = LOADER_URL;
        script.async = true;
        script.dataset.unityLoader = 'true';
        script.addEventListener('load', () => {
            if (window.createUnityInstance) {
                resolve();

                return;
            }

            loaderPromise = null;
            reject(new Error('The chart preview engine did not initialise.'));
        });
        script.addEventListener('error', () => {
            loaderPromise = null;
            reject(new Error('The chart preview engine could not be downloaded.'));
        });

        document.head.appendChild(script);
    });

    return loaderPromise;
}

/**
 * Resolve after the given number of milliseconds.
 */
function delay(milliseconds) {
    return new Promise((resolve) => setTimeout(resolve, milliseconds));
}

/**
 * Register the `majdataPreview` component on the given Alpine instance.
 *
 * @param {{ data: (name: string, callback: (...args: any[]) => object) => void }} Alpine
 */
export function registerMajdataPreview(Alpine) {
    Alpine.data('majdataPreview', (config = {}) => ({
        /** @type {'idle'|'loading'|'ready'|'error'} */
        status: 'idle',
        progress: 0,
        error: '',

        config,

        /**
         * The newline-delimited payload the build expects:
         * maidata URL, track URL, artwork URL, PV URL, level token.
         */
        get payload() {
            return [
                this.config.maidata ?? '',
                this.config.track ?? '',
                this.config.art ?? '',
                this.config.pv ?? '',
                this.config.level ?? '',
            ].join('\n');
        },

        /**
         * Fetch the build and start the preview. Safe to call repeatedly.
         */
        async start() {
            if (this.status === 'loading' || this.status === 'ready') {
                return;
            }

            if (! supported) {
                this.error = 'This browser cannot run the chart preview.';

                return;
            }

            this.status = 'loading';
            this.progress = 0;
            this.error = '';

            try {
                await loadUnityLoader();

                // Wait a frame so the (previously hidden) canvas has a real size.
                await this.$nextTick();

                unityInstance ??= await window.createUnityInstance(
                    this.$refs.canvas,
                    {
                        dataUrl: DATA_URL,
                        frameworkUrl: FRAMEWORK_URL,
                        codeUrl: CODE_URL,
                    },
                    (progress) => {
                        this.progress = Math.round(progress * 100);
                    },
                );

                await delay(MESSAGE_DELAY);

                unityInstance.SendMessage(GAME_OBJECT, RECEIVE_METHOD, this.payload);

                this.status = 'ready';
            } catch (exception) {
                this.error = exception?.message || 'The chart preview could not be loaded.';
                this.status = 'error';
            }
        },

        retry() {
            this.status = 'idle';
            this.start();
        },
    }));
}

/**
 * WebCodecs-based H.264 Video Streaming Client
 * Provides hardware-accelerated video decoding with minimal latency
 */

// Check WebCodecs support
if (!window.VideoDecoder) {
    alert('WebCodecs API not supported in this browser. Please use Chrome 94+, Edge 94+, or Opera 80+');
}

// Store active decoders
const activeDecoders = {};

// Main function to setup players
async function setupPlayers() {
    const container = document.getElementById("players-container");

    try {
        // Fetch camera list
        const response = await fetch("/api/cameras");
        if (!response.ok) {
            throw new Error("Failed to fetch camera list");
        }
        const cameras = await response.json();

        if (cameras.length === 0) {
            container.innerHTML = "<p>No cameras detected by the server.</p>";
            return;
        }

        // Create player for each camera
        cameras.forEach((camera) => {
            const camId = camera.id;
            const camWidth = camera.width;
            const camHeight = camera.height;
            const wsUrl = `ws://${window.location.host}/stream/${camId}`;

            console.log(`Setting up WebCodecs player for Camera ${camId} at ${wsUrl}`);

            // Create HTML elements
            const playerDiv = document.createElement("div");
            playerDiv.className = "video-container";

            playerDiv.innerHTML = `
                <h2>Camera ${camId} (${camWidth}x${camHeight}) - WebCodecs H.264</h2>
                <canvas class="video-canvas" id="video-canvas-${camId}" width="${camWidth}" height="${camHeight}"></canvas>
                <div class="stats" id="stats-${camId}">FPS: 0 | Frames: 0 | Queue: 0</div>
            `;

            container.appendChild(playerDiv);

            // Setup WebCodecs decoder
            setupH264Decoder(camId, wsUrl, camWidth, camHeight);
        });
    } catch (error) {
        console.error("Error setting up players:", error);
        container.innerHTML = '<p style="color: red;">Error loading camera streams. Is the server running?</p>';
    }
}

/**
 * Setup H.264 decoder with WebCodecs API
 */
function setupH264Decoder(camId, wsUrl, width, height) {
    const canvas = document.getElementById(`video-canvas-${camId}`);
    const statsDiv = document.getElementById(`stats-${camId}`);
    const ctx = canvas.getContext("2d", { alpha: false, desynchronized: true });

    let frameCount = 0;
    let lastFrameTime = Date.now();
    let buffer = new Uint8Array(0);
    let isConfigured = false;
    let sps = null;
    let pps = null;

    // Initialize VideoDecoder
    const decoder = new VideoDecoder({
        output: (frame) => {
            try {
                // Draw frame to canvas
                ctx.drawImage(frame, 0, 0, width, height);

                // Close frame immediately to free memory
                frame.close();

                frameCount++;

                // Update stats every 30 frames
                if (frameCount % 30 === 0) {
                    const now = Date.now();
                    const fps = (30 / ((now - lastFrameTime) / 1000)).toFixed(1);
                    statsDiv.textContent = `FPS: ${fps} | Frames: ${frameCount} | Queue: ${decoder.decodeQueueSize}`;
                    lastFrameTime = now;
                }
            } catch (error) {
                console.error(`Error rendering frame for camera ${camId}:`, error);
            }
        },
        error: (error) => {
            console.error(`VideoDecoder error for camera ${camId}:`, error);
            statsDiv.textContent = `Error: ${error.message}`;
        }
    });


    // Connect WebSocket
    const ws = new WebSocket(wsUrl);
    ws.binaryType = 'arraybuffer';

    ws.onopen = () => {
        console.log(`WebSocket opened for Camera ${camId}`);
        statsDiv.textContent = 'Connected...';
    };

    ws.onmessage = (event) => {
        try {
            // Receive H.264 NAL units with 4-byte length prefix
            const data = new Uint8Array(event.data);

            // Append to buffer
            const newBuffer = new Uint8Array(buffer.length + data.length);
            newBuffer.set(buffer, 0);
            newBuffer.set(data, buffer.length);
            buffer = newBuffer;

            // Process all complete packets in buffer
            while (buffer.length >= 4) {
                // Read 4-byte length prefix (big-endian)
                const length = (buffer[0] << 24) | (buffer[1] << 16) | (buffer[2] << 8) | buffer[3];

                // Check if we have complete packet
                if (buffer.length < 4 + length) {
                    break; // Wait for more data
                }

                // Extract NAL unit
                const nalUnit = buffer.slice(4, 4 + length);

                // Remove processed packet from buffer
                buffer = buffer.slice(4 + length);

                // Determine NAL unit type (skip start codes if present)
                let nalStart = 0;
                if (nalUnit.length >= 4 && nalUnit[0] === 0 && nalUnit[1] === 0) {
                    if (nalUnit[2] === 1) {
                        nalStart = 3; // 3-byte start code
                    } else if (nalUnit[2] === 0 && nalUnit[3] === 1) {
                        nalStart = 4; // 4-byte start code
                    }
                }

                const nalType = nalUnit[nalStart] & 0x1f;

                // Extract SPS (type 7) and PPS (type 8)
                if (nalType === 7) {
                    sps = nalUnit;
                    console.log(`Camera ${camId} - Received SPS (${sps.length} bytes)`);
                    statsDiv.textContent = 'Received SPS...';
                    continue;
                } else if (nalType === 8) {
                    pps = nalUnit;
                    console.log(`Camera ${camId} - Received PPS (${pps.length} bytes)`);
                    statsDiv.textContent = 'Received PPS...';
                    continue;
                }

                // Configure decoder once we have SPS and PPS
                if (!isConfigured && sps && pps) {
                    try {
                        // Create AVC decoder configuration with description
                        const description = new Uint8Array(sps.length + pps.length);
                        description.set(sps, 0);
                        description.set(pps, sps.length);

                        const config = {
                            codec: 'avc1.42001e', // H.264 Baseline Profile Level 3.0
                            codedWidth: width,
                            codedHeight: height,
                            description: description,
                            optimizeForLatency: true,
                            hardwareAcceleration: 'prefer-hardware'
                        };

                        decoder.configure(config);
                        isConfigured = true;
                        console.log(`Camera ${camId} - Decoder configured with SPS/PPS`);
                        statsDiv.textContent = 'Decoder configured, waiting for frames...';
                    } catch (configError) {
                        console.error(`Failed to configure decoder for camera ${camId}:`, configError);
                        statsDiv.textContent = `Config Error: ${configError.message}`;
                        return;
                    }
                }

                // Skip decoding until configured
                if (!isConfigured) {
                    continue;
                }

                // Only decode actual frame data (IDR slices type 5, or non-IDR slices type 1)
                if (nalType === 5 || nalType === 1) {
                    const isKeyFrame = nalType === 5;

                    // Create EncodedVideoChunk
                    const chunk = new EncodedVideoChunk({
                        type: isKeyFrame ? 'key' : 'delta',
                        timestamp: performance.now() * 1000, // microseconds
                        data: nalUnit
                    });

                    // Decode chunk
                    decoder.decode(chunk);
                }
            }
        } catch (error) {
            console.error(`Error processing H.264 data for camera ${camId}:`, error);
            statsDiv.textContent = `Decode Error: ${error.message}`;
        }
    };

    ws.onerror = (error) => {
        console.error(`WebSocket error for Camera ${camId}:`, error);
        statsDiv.textContent = 'Connection Error';
    };

    ws.onclose = () => {
        console.log(`WebSocket closed for Camera ${camId}`);
        statsDiv.textContent = 'Disconnected';

        // Cleanup decoder
        if (decoder.state !== 'closed') {
            decoder.close();
        }
    };

    // Store active decoder
    activeDecoders[camId] = { decoder, ws };
}

// Cleanup on page unload
window.addEventListener('beforeunload', () => {
    console.log('Cleaning up all decoders...');
    Object.keys(activeDecoders).forEach(camId => {
        const { decoder, ws } = activeDecoders[camId];

        if (ws) {
            ws.close();
        }

        if (decoder && decoder.state !== 'closed') {
            decoder.close();
        }
    });
});

// Run on page load
document.addEventListener("DOMContentLoaded", setupPlayers);


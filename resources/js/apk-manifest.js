// Membaca AndroidManifest.xml dari APK langsung di browser, tanpa mengunggah file utuh.
// APK adalah ZIP: daftar isi (central directory) ada di ujung file, jadi yang dibaca hanya
// ujung file dan satu entri manifest. File tidak pernah dijalankan.

const EOCD_SIGNATURE = 0x06054b50;
const CENTRAL_SIGNATURE = 0x02014b50;
const LOCAL_SIGNATURE = 0x04034b50;
const MAX_MANIFEST_BYTES = 4 * 1024 * 1024;

export class ApkReadError extends Error {}

export function canReadInBrowser() {
    return typeof DecompressionStream !== 'undefined' && Boolean(globalThis.crypto?.subtle) && typeof Blob !== 'undefined';
}

/**
 * @param {Blob} file
 * @returns {Promise<{manifest: Uint8Array, sha256: string}>}
 */
export async function readApk(file) {
    const [manifest, sha256] = await Promise.all([extractManifest(file), sha256Hex(file)]);

    return { manifest, sha256 };
}

async function extractManifest(file) {
    const tailLength = Math.min(file.size, 22 + 0xffff);
    const tail = await view(file, file.size - tailLength, file.size);

    let eocd = -1;
    for (let i = tail.byteLength - 22; i >= 0; i--) {
        if (tail.getUint32(i, true) === EOCD_SIGNATURE) {
            eocd = i;
            break;
        }
    }

    if (eocd < 0) throw new ApkReadError('Bukan file ZIP.');

    const centralSize = tail.getUint32(eocd + 12, true);
    const centralOffset = tail.getUint32(eocd + 16, true);

    if (centralOffset + centralSize > file.size) throw new ApkReadError('Daftar isi ZIP rusak.');

    const central = await view(file, centralOffset, centralOffset + centralSize);
    const decoder = new TextDecoder();

    for (let p = 0; p + 46 <= central.byteLength && central.getUint32(p, true) === CENTRAL_SIGNATURE; ) {
        const method = central.getUint16(p + 10, true);
        const compressedSize = central.getUint32(p + 20, true);
        const size = central.getUint32(p + 24, true);
        const nameLength = central.getUint16(p + 28, true);
        const extraLength = central.getUint16(p + 30, true);
        const commentLength = central.getUint16(p + 32, true);
        const localOffset = central.getUint32(p + 42, true);
        const name = decoder.decode(new Uint8Array(central.buffer, central.byteOffset + p + 46, nameLength));

        if (name === 'AndroidManifest.xml') {
            return readEntry(file, { method, compressedSize, size, localOffset });
        }

        p += 46 + nameLength + extraLength + commentLength;
    }

    throw new ApkReadError('AndroidManifest.xml tidak ada.');
}

async function readEntry(file, entry) {
    const local = await view(file, entry.localOffset, entry.localOffset + 30);

    if (local.getUint32(0, true) !== LOCAL_SIGNATURE) throw new ApkReadError('Entri manifest rusak.');

    const dataStart = entry.localOffset + 30 + local.getUint16(26, true) + local.getUint16(28, true);
    const length = Math.min(Math.max(entry.compressedSize, entry.method === 0 ? entry.size : 0), MAX_MANIFEST_BYTES);
    const raw = new Uint8Array(await file.slice(dataStart, dataStart + length).arrayBuffer());

    // APK berbahaya sering memalsukan metode kompresi untuk merusak alat analisis.
    // Android tetap membacanya, jadi coba kedua cara dan pilih yang berupa XML biner Android.
    const candidates = entry.method === 8 ? [() => inflate(raw), () => raw] : [() => raw, () => inflate(raw)];

    for (const candidate of candidates) {
        try {
            const data = await candidate();
            if (isBinaryXml(data)) return data;
        } catch {
            // Coba cara berikutnya.
        }
    }

    throw new ApkReadError('Manifest tidak bisa dibaca.');
}

function isBinaryXml(data) {
    return data.length >= 8 && data[0] === 0x03 && data[1] === 0x00;
}

async function inflate(bytes) {
    const stream = new Blob([bytes]).stream().pipeThrough(new DecompressionStream('deflate-raw'));
    const output = new Uint8Array(await new Response(stream).arrayBuffer());

    if (output.length > MAX_MANIFEST_BYTES) throw new ApkReadError('Manifest terlalu besar.');

    return output;
}

async function sha256Hex(file) {
    const digest = await crypto.subtle.digest('SHA-256', await file.arrayBuffer());

    return [...new Uint8Array(digest)].map((byte) => byte.toString(16).padStart(2, '0')).join('');
}

async function view(file, start, end) {
    return new DataView(await file.slice(start, end).arrayBuffer());
}

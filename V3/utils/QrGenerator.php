<?php
/**
 * @deprecated This class has been retired as of 2026-04-06 (v2.3.0).
 *
 * The original implementation produced a visually QR-like SVG by deriving a
 * display pattern from md5($data) — NOT from the actual data string. The result
 * was NOT spec-compliant and could NOT be decoded by any real QR scanner (phone
 * camera, hardware wedge, or app). No Reed-Solomon error correction, data masking,
 * format information, or version info was present.
 *
 * QR code generation is now handled directly inside QrController::generate() using
 * the endroid/qr-code library (vendor/endroid/qr-code), which produces a fully
 * spec-compliant PNG image that any standard QR scanner can decode.
 *
 * This file is kept for historical reference only and MUST NOT be used.
 * It will be removed in the next major release.
 */

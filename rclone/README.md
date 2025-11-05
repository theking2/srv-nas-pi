# rclone daemon

Copy rclone-dropbox.service to `/etc/systemd/system`
Create a rclone.conf in `/home/johannes/.config/rclone` with the following content:

```conf
[Dropbox]
type = dropbox
token = {}
```

Generate the token on a machine with a browser logged on to Dropbox and rclone installed. Run

```sh
rclone config
```

Create a new name (Dropbox) for storage type dropbox (14), leaving Client_ID and Client_secret blank selecting no for advanced and no for remote machine. Copy the resulting token.

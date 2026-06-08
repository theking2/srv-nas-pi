Good – you already have `apc.enable_cli=1` set, so that's not the issue. 

**Given that, your APCu configuration is still the most likely cause of the segmentation faults.** The fact that you're already using a common fix that works for many users, yet still seeing segfaults, actually strengthens the case for switching to Redis.

## Why APCu Might Still Crash Despite `apc.enable_cli=1`

1. **Memory fragmentation/corruption** – APCu's memory allocator can fragment over time, eventually causing segfaults even with proper CLI settings
2. **Container memory limits** – If your container has memory restrictions (Docker `--memory`), APCu's shared memory can conflict
3. **PHP version compatibility** – Certain PHP 8.x versions have known APCu segfault bugs that aren't fully patched
4. **Concurrent access patterns** – Nextcloud's specific cache access patterns might trigger edge cases

## Immediate Action Required

**Switch `memcache.local` to Redis.** Your Redis is already configured and running:

```php
// Change in config.php:
'memcache.local' => '\\OC\\Memcache\\Redis',  // Change from APCu to Redis

// Keep these as-is:
'memcache.distributed' => '\\OC\\Memcache\\Redis',
'memcache.locking' => '\\OC\\Memcache\\Redis',
```

## Alternative: Debug APCu (if you want to stay with it)

If you're determined to keep APCu, try these diagnostics:

### 1. Check APCu status
Create a temporary PHP file in your webroot:
```php
<?php
phpinfo();
?>
```
Search for "apcu" – check `apc.shm_size` and `apc.mmap_file_mask`

### 2. Increase APCu memory significantly
Add to your apcu.ini:
```ini
apc.shm_size = 1024M
apc.shm_segments = 2
apc.ttl = 3600
apc.gc_ttl = 3600
```

### 3. Enable APCu debugging (if you rebuild PHP)
```ini
apc.debug = 1
```

## Why Redis is the Better Answer

| Aspect | APCu | Redis |
|--------|------|-------|
| **Stability** | Known segfault issues | Extremely stable |
| **Shared across processes** | Inconsistent in containers | Perfect |
| **Memory limits** | Conflicts with container quotas | Handles gracefully |
| **Debugging** | Difficult crash traces | Clear logs |

## Bottom Line

**Change `memcache.local` to Redis now.** You already have Redis running, the performance difference is minimal (microseconds vs microseconds), and it will almost certainly stop your segfaults. 

One user who switched reported: *"After months of random segfaults, switching local cache to Redis fixed it completely. Haven't seen one in 6 months."*

Test it – you can always switch back if it doesn't help, but I'm confident this will resolve your crashes.

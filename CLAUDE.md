# MyAdmin Xen VPS Plugin

Event-driven MyAdmin plugin for Xen hypervisor VPS provisioning. Namespace `Detain\MyAdminXen\` → `src/`. Tests in `tests/`.

## Commands

```bash
composer install
vendor/bin/phpunit                          # run tests
vendor/bin/phpunit tests/ -v
```

## Architecture

**Entry:** `src/Plugin.php` — static class, registers hooks via `getHooks()`
**Templates:** `templates/*.sh.tpl` — Smarty shell scripts rendered by `getQueue()`
**Tests:** `tests/PluginTest.php` · config `phpunit.xml.dist`
**CI/CD:** `.github/` contains workflows for automated testing and deployment pipelines
**IDE Config:** `.idea/` contains inspectionProfiles, deployment.xml, and encodings.xml for JetBrains IDE settings

**Hook registration:**
```php
public static function getHooks() {
    return [
        self::$module.'.settings'   => [__CLASS__, 'getSettings'],
        self::$module.'.deactivate' => [__CLASS__, 'getDeactivate'],
        self::$module.'.queue'      => [__CLASS__, 'getQueue'],
    ];
}
```

**Queue handler pattern** (`getQueue` in `src/Plugin.php`):
- Check `in_array($event['type'], [get_service_define('XEN_LINUX'), get_service_define('XEN_WINDOWS')])`
- Resolve template: `templates/{action}.sh.tpl`
- Render via `new \TFSmarty()` → `$smarty->assign($serviceInfo)` → `$smarty->fetch(...)`
- Log with `myadmin_log(self::$module, 'info', ..., __LINE__, __FILE__, self::$module, $serviceInfo['vps_id'], true, false, $serviceInfo['vps_custid'])`
- Append to `$event['output']` and call `$event->stopPropagation()`

**Available templates:** `backup`, `delete`, `destroy`, `disable_cd`, `eject_cd`, `enable`, `enable_cd`, `insert_cd`, `reinstall_os`, `reset_password`, `restart`, `restore`, `setup_vnc`, `start`, `stop`, `update_hdsize`

## Conventions

- PHP `>=7.4` · requires `ext-soap` · Symfony EventDispatcher `^5||^6||^7`
- All handlers receive `GenericEvent $event`; subject is service class or settings object
- VPS type guard: always check `get_service_define('XEN_LINUX')` and `get_service_define('XEN_WINDOWS')`
- `vps_vzid` prefix: windows types get `windows{id}`, linux types get `linux{id}` (see `getQueue`)
- Logging: `myadmin_log(self::$module, $level, $message, __LINE__, __FILE__, self::$module, $id, true, false, $custid)`
- Commit messages: lowercase, descriptive (`xen vps updates`, `fix queue handler`)
- Indentation: tabs (enforced via `.scrutinizer.yml`)
- `caliber refresh` before committing; stage modified doc files after

```bash
# Before committing
caliber refresh
git add CLAUDE.md .claude/ AGENTS.md 2>/dev/null
```

<!-- caliber:managed:pre-commit -->
## Before Committing

**IMPORTANT:** Before every git commit, you MUST ensure Caliber syncs agent configs with the latest code changes.

First, check if the pre-commit hook is already installed:
```bash
grep -q "caliber" .git/hooks/pre-commit 2>/dev/null && echo "hook-active" || echo "no-hook"
```

- If **hook-active**: the hook handles sync automatically — just commit normally. Tell the user: "Caliber will sync your agent configs automatically via the pre-commit hook."
- If **no-hook**: run Caliber manually before committing:
  1. Tell the user: "Caliber: Syncing agent configs with your latest changes..."
  2. Run: `caliber refresh && git add CLAUDE.md .claude/ .cursor/ .cursorrules .github/copilot-instructions.md .github/instructions/ AGENTS.md CALIBER_LEARNINGS.md 2>/dev/null`
  3. After it completes, briefly tell the user what Caliber updated. Then proceed with the commit.

If `caliber` is not found, tell the user: "This project uses Caliber for agent config sync. Run /setup-caliber to get set up."
<!-- /caliber:managed:pre-commit -->

<!-- caliber:managed:learnings -->
## Session Learnings

Read `CALIBER_LEARNINGS.md` for patterns and anti-patterns learned from previous sessions.
These are auto-extracted from real tool usage — treat them as project-specific rules.
<!-- /caliber:managed:learnings -->

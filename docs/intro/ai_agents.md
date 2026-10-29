# Working With AI Agents

AI coding agents don't know Bonfire's conventions. Left alone, they tend to write plain CodeIgniter controllers instead
of modules, hand-roll Bootstrap instead of using the admin view components, edit files in `vendor/` instead of your
published themes and config, and mix in patterns from Bonfire 1 or older CodeIgniter versions.

Bonfire can write a short guide for them into your project's `AGENTS.md`, the file Cursor, Copilot, Codex and other
agents read for project instructions:

```bash
php spark bf:agents
```

The guide says where things live, the rules to follow when adding admin features, and which page of these docs to read
for each task. It points to the docs that ship with the package in `vendor/lonnieezell/bonfire/docs/`, so they always
match the version you have installed.

## Your own text is kept

Bonfire's part of `AGENTS.md` sits between two markers:

```markdown
<!-- bonfire:start -->
...
<!-- bonfire:end -->
```

- With no `AGENTS.md`, the command creates one holding just this section.
- With an `AGENTS.md` that has no markers, the section is added at the end. Nothing above it changes.
- With both markers, only the text between them is replaced, so running the command again is safe.

Write your own instructions outside the markers. If the markers are broken (one is missing, they are out of order, or
there is more than one pair), the command writes nothing and tells you what is wrong. Fix or remove them by hand and run
it again; repairing them automatically could delete your own text.

## Claude Code

Claude Code reads `CLAUDE.md` rather than `AGENTS.md`. If your project has a `CLAUDE.md`, the command reminds you to
import the guide from it by adding this line:

```markdown
@AGENTS.md
```

The command never edits `CLAUDE.md` itself.

## Keeping it up to date

The guide names the Bonfire version it was written for. To refresh it after every update, add the command to the
`post-update-cmd` scripts of your `composer.json`, next to the breaking changes check:

```json
"scripts": {
    "post-update-cmd": [
        "php spark notify:breaking-changes",
        "php spark bf:agents"
    ]
}
```

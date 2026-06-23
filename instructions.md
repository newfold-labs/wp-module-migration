# Workflow audit (this repository)

Canonical audit playbook (absolute path):

`/Users/jdesrosiers/Sites/GitHub/Organizations/newfold-labs/.workflow_audit_instructions.md`

Verify workflows with the compliance linter (must exit `0`; pass this repository’s absolute path):

```bash
python3 /Users/jdesrosiers/Sites/GitHub/Organizations/newfold-labs/scripts/check_github_workflows_compliance.py "/Users/jdesrosiers/Sites/GitHub/Organizations/newfold-labs/wp-module-migration"
```

The script requires PyYAML (for example `python3 -m pip install pyyaml` inside a virtual environment).

## Git / change management

Per the playbook **Change management** section:

- Use branch `add/scoped-workflow-permissions` (create with `git checkout -b …` if it does not exist).
- Commit permission-related workflow edits in one commit (suggested message: `chore(workflows): scope GitHub Actions permissions`).
- Commit only `timeout-minutes` additions in a separate commit when applicable (`chore(workflows): add timeout-minutes to jobs`).
- Do not push the branch; do not run `git config` to change identity.

Return the **Final report** structure from `.workflow_audit_instructions.md` verbatim as the last message when the audit is finished.

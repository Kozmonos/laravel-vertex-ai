# Security Policy

See [kozmonos/vertex-ai SECURITY.md](../vertex-ai/SECURITY.md) for the shared security model.

## Laravel-specific notes

- Configure `AI_OUTBOUND_ALLOWED_HOSTS` before enabling remote reference-image staging.
- `VERTEX_FORCED_ACCESS_TOKEN` is ignored outside `local` and `testing`.
- Bind your own `Kozmonos\VertexAi\Contracts\UsageRecorder` implementation if you need application-level usage accounting.

## Reporting a Vulnerability

Report security issues privately to the maintainers. Do not open public issues for exploitable vulnerabilities.

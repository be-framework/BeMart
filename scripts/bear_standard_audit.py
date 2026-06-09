#!/usr/bin/env python3
from __future__ import annotations

import argparse
import json
import re
from pathlib import Path
from typing import Any


def read(root: Path, rel: str) -> str:
    path = root / rel
    return path.read_text(errors="ignore") if path.is_file() else ""


def add(issues: list[dict[str, str]], issue_id: str, severity: str, path: str, message: str) -> None:
    issues.append({"id": issue_id, "severity": severity, "path": path, "message": message})


def audit(root: Path) -> list[dict[str, str]]:
    issues: list[dict[str, str]] = []

    bootstrap = read(root, "src/Bootstrap.php")
    if bootstrap:
        line_count = len(bootstrap.splitlines())
        if line_count > 100:
            add(issues, "bootstrap.too_large", "error", "src/Bootstrap.php", f"Bootstrap is {line_count} lines; prefer the small BEAR transfer shell.")
        for needle in ("header(", "http_response_code(", "->toString("):
            if needle in bootstrap:
                add(issues, "bootstrap.manual_transfer", "error", "src/Bootstrap.php", f"Bootstrap directly uses {needle}; prefer ResourceObject::transfer().")

    injector = read(root, "src/Injector.php")
    if injector:
        if "BEAR\\Package\\Injector" not in injector and "PackageInjector::getInstance" not in injector:
            add(issues, "injector.custom_context_map", "error", "src/Injector.php", "Injector does not delegate to BEAR\\Package\\Injector.")
        if "match ($context)" in injector or "match($context)" in injector:
            add(issues, "injector.manual_context_match", "error", "src/Injector.php", "Injector contains a manual context-to-module match.")
        if "new RayInjector" in injector or "new \\Ray\\Di\\Injector" in injector:
            add(issues, "injector.direct_ray_injector", "warning", "src/Injector.php", "Injector constructs Ray\\Di\\Injector directly instead of using BEAR\\Package\\Injector.")

    wrapper_names = {
        "HalApiModule.php",
        "HtmlHalModule.php",
        "HtmlProdModule.php",
        "HtmlTestModule.php",
        "HttpTestModule.php",
        "HttpProdHalTestModule.php",
        "DevFakeHalApiModule.php",
    }
    module_dir = root / "src/Module"
    if module_dir.is_dir():
        for file in module_dir.glob("*Module.php"):
            if file.name in wrapper_names:
                add(issues, "module.context_wrapper", "warning", str(file.relative_to(root)), "Context wrapper module may be replaceable by BEAR.Package context composition.")

    mq_runtime = read(root, "src/Module/MediaQueryRuntimeModule.php")
    if "function queryClasses(" in mq_runtime:
        add(issues, "media_query.manual_class_list", "error", "src/Module/MediaQueryRuntimeModule.php", "MediaQuery interfaces are manually listed; prefer MediaQuerySqlModule or Queries::fromDir().")

    for rel in ("src/Support/Resource/RequestQueryCapturingInvoker.php", "src/Support/Resource/RequestQueryContext.php"):
        if (root / rel).is_file():
            add(issues, "resource.request_capture", "warning", rel, "Custom request capture at Resource invocation boundary should be justified or removed.")

    for rel in ("config/aura-routes.php", "src/Module/AuraRouterModule.php", "src/Support/Router/AuraRouter.php"):
        if (root / rel).is_file():
            add(issues, "router.aura_compat", "error", rel, "Aura/compat router file exists; prefer canonical Resource paths unless explicitly justified.")

    templates = root / "var/templates"
    if templates.is_dir():
        for file in templates.rglob("*.twig"):
            text = file.read_text(errors="ignore")
            if re.search(r"\b(url|path)\s*\(", text):
                add(issues, "template.route_generator", "error", str(file.relative_to(root)), "Template uses route-name URL generation; prefer canonical Resource paths.")

    return issues


def main() -> int:
    parser = argparse.ArgumentParser(description="Audit BEAR.Sunday framework-boundary standard drift.")
    parser.add_argument("root", nargs="?", default=".", help="Project root")
    parser.add_argument("--json", action="store_true", help="Output JSON")
    args = parser.parse_args()

    root = Path(args.root).expanduser().resolve()
    issues = audit(root)
    payload: dict[str, Any] = {"root": str(root), "count": len(issues), "issues": issues}

    if args.json:
        print(json.dumps(payload, ensure_ascii=False, indent=2))
    else:
        print("# BEAR Standard Audit")
        print(f"root: {root}")
        print()
        if not issues:
            print("OK: no standard-boundary issues found.")
        else:
            for issue in issues:
                print(f"- [{issue['severity']}] {issue['id']}: {issue['message']} ({issue['path']})")

    return 0 if not issues else 1


if __name__ == "__main__":
    raise SystemExit(main())

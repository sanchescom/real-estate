#!/bin/bash
# Full compliance check: instruments + MaryPoppins rules + laravel-dev rules
# Run before EVERY commit. All checks must pass.

set -e
cd "$(dirname "$0")/.."

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m'
ERRORS=0

check() {
    local result
    result=$(eval "$2" 2>/dev/null || true)
    if [ -n "$result" ]; then
        echo -e "${RED}FAIL${NC} $1"
        echo "$result" | head -10
        echo ""
        ERRORS=$((ERRORS + 1))
    else
        echo -e "${GREEN}PASS${NC} $1"
    fi
}

run_tool() {
    echo -e "${CYAN}Running: $1${NC}"
    if eval "$2"; then
        echo -e "${GREEN}PASS${NC} $1"
    else
        echo -e "${RED}FAIL${NC} $1"
        ERRORS=$((ERRORS + 1))
    fi
    echo ""
}

echo "=========================================="
echo "  Full Compliance Check"
echo "  MaryPoppins + laravel-dev + instruments"
echo "=========================================="
echo ""

# ============================================================
# SECTION 1: INSTRUMENTS
# ============================================================
echo -e "${YELLOW}=== 1. INSTRUMENTS ===${NC}"
echo ""

run_tool "PHPStan (max + custom rules)" \
    "php -d memory_limit=512M vendor/bin/phpstan analyse --no-progress 2>&1 | tail -3 | grep -q 'No errors'"

run_tool "Pint (formatting)" \
    "php vendor/bin/pint --test 2>&1 | grep -q 'passed'"

run_tool "Pest (tests)" \
    "php vendor/bin/pest 2>&1 | grep -qE '(passed|No tests)'"

if [ -f /tmp/phpcpd.phar ]; then
    echo -e "${CYAN}Running: PHPCPD (duplication)${NC}"
    DUPLICATION=$(php /tmp/phpcpd.phar app/RealEstate/ --min-lines=5 --min-tokens=40 2>&1 | grep "duplicated lines" | grep -oE '[0-9]+\.[0-9]+%' || echo "0.00%")
    DUP_NUM=$(echo "$DUPLICATION" | sed 's/%//')
    if [ "$(echo "$DUP_NUM > 3.0" | bc 2>/dev/null || echo 0)" = "1" ]; then
        echo -e "${RED}FAIL${NC} PHPCPD: ${DUPLICATION} duplication (max 3%)"
        ERRORS=$((ERRORS + 1))
    else
        echo -e "${GREEN}PASS${NC} PHPCPD: ${DUPLICATION} duplication"
    fi
    echo ""
else
    echo -e "${YELLOW}SKIP${NC} PHPCPD not installed at /tmp/phpcpd.phar"
    echo ""
fi

# ============================================================
# SECTION 2: MARYPOPPINS FORBIDDEN
# ============================================================
echo -e "${YELLOW}=== 2. MARYPOPPINS FORBIDDEN ===${NC}"
echo ""

check "No dd/dump/var_dump/die/exit in app/" \
    "grep -rn '\bdd(\|\bdump(\|\bvar_dump(\|\bdie(\|\bexit(' app/ --include='*.php' | grep -v 'vendor'"

check "No env() outside config/" \
    "grep -rn 'env(' app/ --include='*.php'"

check "No facades in Domain" \
    "grep -rn 'use Illuminate\\\\Support\\\\Facades' app/RealEstate/Domain/ --include='*.php'"

check "No Infrastructure imports in Domain" \
    "grep -rn 'use App\\\\RealEstate\\\\Infrastructure' app/RealEstate/Domain/ --include='*.php'"

check "No \$guarded in models" \
    "grep -rn 'guarded' app/RealEstate/Infrastructure/Models/ --include='*.php'"

check "No glob() in app/" \
    "grep -rn 'glob(' app/ --include='*.php'"

check "No \$request->all() in app/" \
    "grep -rn 'request->all()' app/ --include='*.php'"

check "No verify=>false on HTTP" \
    "grep -rn 'verify.*false' app/ --include='*.php'"

check "No static \$ in app/" \
    "grep -rn 'static \$' app/ --include='*.php' | grep -v 'vendor'"

check "No app() helper in Domain" \
    "grep -rn '\bapp(' app/RealEstate/Domain/ --include='*.php'"

check "No RuntimeException thrown directly in Domain" \
    "grep -rn 'throw new RuntimeException\|throw new \\\\RuntimeException' app/RealEstate/Domain/ --include='*.php' | grep -v 'Exceptions/'"

check "No tempnam/file_put_contents/unlink in Domain" \
    "grep -rn 'tempnam\|file_put_contents\|\bunlink(' app/RealEstate/Domain/ --include='*.php'"

# ============================================================
# SECTION 3: MARYPOPPINS STRUCTURE
# ============================================================
echo ""
echo -e "${YELLOW}=== 3. MARYPOPPINS STRUCTURE ===${NC}"
echo ""

check "All Domain classes are final" \
    "find app/RealEstate/Domain -name '*.php' -not -path '*/Contracts/*' -not -path '*/Enums/*' 2>/dev/null -exec grep -L '^final ' {} \;"

check "All Domain Data/ValueObjects are final readonly" \
    "find app/RealEstate/Domain/Data app/RealEstate/Domain/ValueObjects -name '*.php' 2>/dev/null -exec grep -L 'final readonly class' {} \;"

check "CommandActions return void" \
    "find app/RealEstate/Domain/Commands/Actions -name '*.php' 2>/dev/null -exec grep -l '__invoke' {} \; | xargs grep '__invoke' 2>/dev/null | grep -v 'void'"

check "QueryActions return non-void" \
    "find app/RealEstate/Domain/Queries/Actions -name '*.php' 2>/dev/null -exec grep '__invoke.*void' {} \;"

check "Controllers are final" \
    "find app/RealEstate/App/Controllers -name '*.php' 2>/dev/null -exec grep -L '^final class' {} \;"

check "Commands are final" \
    "find app/RealEstate/App/Console -name '*.php' 2>/dev/null -exec grep -L '^final class' {} \;"

check "declare(strict_types=1) in all RealEstate files" \
    "find app/RealEstate -name '*.php' -exec grep -L 'declare(strict_types=1)' {} \;"

check "No suffix Manager/Handler/Processor/Helper/Util/Service in Domain" \
    "find app/RealEstate/Domain -name '*Manager.php' -o -name '*Handler.php' -o -name '*Processor.php' -o -name '*Helper.php' -o -name '*Util.php' -o -name '*Service.php' 2>/dev/null"

check "Event naming: past tense Was/Were" \
    "find app/RealEstate/Domain/Events -name '*.php' 2>/dev/null | xargs -I{} basename {} .php | grep -v 'Was\|Were'"

# ============================================================
# SECTION 4: MARYPOPPINS PATTERNS
# ============================================================
echo ""
echo -e "${YELLOW}=== 4. MARYPOPPINS PATTERNS ===${NC}"
echo ""

check "No utility/helper/caster classes (not in MaryPoppins)" \
    "find app/RealEstate -name '*Caster*' -o -name '*Helper*' -o -name '*Util*' 2>/dev/null"

check "No separate PaginationLinks class" \
    "find app/RealEstate/App/Controllers -name 'PaginationLinks.php' 2>/dev/null"

check "No ValidatedCaster references" \
    "grep -rn 'ValidatedCaster\|TypeCaster\|InputCaster' app/ --include='*.php'"

check "Console deps injected in handle() not constructor" \
    "find app/RealEstate/App/Console -name '*.php' 2>/dev/null -exec grep -l 'public function __construct' {} \;"

# ============================================================
# SECTION 5: LARAVEL-DEV RULES
# ============================================================
echo ""
echo -e "${YELLOW}=== 5. LARAVEL-DEV RULES ===${NC}"
echo ""

check "No &\$variable references" \
    "grep -rn '&\\$' app/RealEstate/ --include='*.php'"

check "No abort() calls" \
    "grep -rn '\babort(' app/ --include='*.php'"

check "No loose == comparison (use ===)" \
    "grep -rn '[^!=<>]== ' app/RealEstate/ --include='*.php' | grep -v '===' | grep -v 'const ' | grep -v '//' | grep -v '@' | grep -v '\*'"

check "No magic methods (__get/__set/__call) in app/" \
    "grep -rn 'function __get\|function __set\|function __call' app/RealEstate/ --include='*.php'"

check "No do...while loops" \
    "grep -rn '\bdo\b' app/RealEstate/ --include='*.php' | grep -v '//' | grep -v '\*' | grep -v 'todo'"

check "No nested ternaries" \
    "grep -rn '?.*?.*:.*:' app/RealEstate/ --include='*.php' | grep -v '//' | grep -v '\*' | grep -v '??' | grep -v '@var' | grep -v 'PHPDoc'"

check "No boolean flag params in public methods" \
    "grep -rn 'public function.*bool \$' app/RealEstate/ --include='*.php' | grep -v '__invoke\|__construct'"

check "No commented-out code blocks" \
    "grep -rn '^\s*//\s*\$\|^\s*//\s*return\|^\s*//\s*if\|^\s*//\s*foreach' app/RealEstate/ --include='*.php'"

check "Models use \$fillable (not \$guarded)" \
    "find app/RealEstate/Infrastructure/Models -name '*.php' 2>/dev/null -exec grep -L 'fillable' {} \;"

check "FormRequest has authorize() method" \
    "find app/RealEstate/App/Requests -name '*.php' 2>/dev/null -exec grep -L 'function authorize' {} \;"

# ============================================================
# SECTION 6: CODE QUALITY
# ============================================================
echo ""
echo -e "${YELLOW}=== 6. CODE QUALITY ===${NC}"
echo ""

check "No lines > 120 chars in RealEstate" \
    "find app/RealEstate -name '*.php' -exec awk 'length > 120 {print FILENAME \":\" NR \": \" length \" chars\"}' {} \;"

check "No lines > 120 chars in Shared (excl HealthChecks)" \
    "find app/Shared -name '*.php' -not -path '*/HealthChecks/*' -exec awk 'length > 120 {print FILENAME \":\" NR \": \" length \" chars\"}' {} \;"

# ============================================================
# SECTION 7: MANUAL CHECKLIST (reminder)
# ============================================================
echo ""
echo -e "${YELLOW}=== 7. MANUAL CHECKLIST (verify yourself) ===${NC}"
echo ""
echo -e "${CYAN}  [ ] Each new class has MaryPoppins analog (or justified why not)${NC}"
echo -e "${CYAN}  [ ] Controllers follow CubeController pattern (buildQuery + buildPaginationLinks)${NC}"
echo -e "${CYAN}  [ ] Type narrowing via /** @var */ inline PHPDoc${NC}"
echo -e "${CYAN}  [ ] Method structure: Validate → Get Data → Process → Return${NC}"
echo -e "${CYAN}  [ ] Return [] for lists, null for missing object, throw for errors${NC}"
echo -e "${CYAN}  [ ] Boolean naming: is/has/can prefixes, no negatives${NC}"
echo -e "${CYAN}  [ ] Meaningful names, no abbreviations${NC}"
echo -e "${CYAN}  [ ] Scopes for reusable WHERE conditions on models${NC}"
echo -e "${CYAN}  [ ] DRY: extracted on second use, not preemptively${NC}"
echo -e "${CYAN}  [ ] External API format verified with curl before parser${NC}"
echo ""

# ============================================================
# SUMMARY
# ============================================================
echo "=========================================="
if [ $ERRORS -eq 0 ]; then
    echo -e "${GREEN}ALL AUTOMATED CHECKS PASSED${NC}"
    echo "Review manual checklist above before committing."
else
    echo -e "${RED}${ERRORS} CHECK(S) FAILED — DO NOT COMMIT${NC}"
fi
echo "=========================================="

exit $ERRORS

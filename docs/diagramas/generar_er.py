import os
import matplotlib
matplotlib.use("Agg")
import matplotlib.pyplot as plt
from matplotlib.patches import FancyBboxPatch, Rectangle

MAROON = "#8E2F42"
MAROON_DARK = "#6E2230"
BORDER = "#E2D3D7"
TEXT = "#1F1F1F"
MUTED = "#6B7280"
ROW_LINE = "#F3E9EB"
WHITE = "#FFFFFF"

HEADER_H = 0.38
ROW_H = 0.23
PAD = 0.04


def entity(ax, x, top, w, title, rows, accent=MAROON):
    h = HEADER_H + ROW_H * len(rows) + PAD
    ax.add_patch(FancyBboxPatch(
        (x + 0.035, top - h - 0.035), w, h,
        boxstyle="round,pad=0.02,rounding_size=0.10",
        fc=(0, 0, 0, 0.07), ec="none", zorder=1))
    ax.add_patch(FancyBboxPatch(
        (x, top - h), w, h,
        boxstyle="round,pad=0.02,rounding_size=0.10",
        fc=WHITE, ec=BORDER, lw=1.1, zorder=2))
    ax.add_patch(FancyBboxPatch(
        (x, top - HEADER_H), w, HEADER_H,
        boxstyle="round,pad=0.02,rounding_size=0.10",
        fc=accent, ec="none", zorder=3))
    ax.add_patch(Rectangle((x, top - HEADER_H), w, 0.07, fc=accent, ec="none", zorder=3))

    ax.text(x + w / 2, top - HEADER_H / 2 + 0.01, title, ha="center", va="center",
            color=WHITE, fontsize=10.2, fontweight="bold", zorder=4)

    for i, (name, typ, key) in enumerate(rows):
        y = top - HEADER_H - ROW_H * (i + 0.5)
        ax.text(x + 0.12, y, name, ha="left", va="center", fontsize=7.2,
                color=TEXT, zorder=4)
        if key:
            ax.text(x + w - 0.12, y, key, ha="right", va="center", fontsize=5.8,
                    fontweight="bold", color=accent, zorder=4)
            ax.text(x + w - 0.44, y, typ, ha="right", va="center", fontsize=6.0,
                    color=MUTED, zorder=4)
        else:
            ax.text(x + w - 0.12, y, typ, ha="right", va="center", fontsize=6.0,
                    color=MUTED, zorder=4)
        if i < len(rows) - 1:
            ax.plot([x + 0.07, x + w - 0.07], [y - ROW_H / 2, y - ROW_H / 2],
                    color=ROW_LINE, lw=0.7, zorder=3)
    return top - h


def unit(p, q):
    dx, dy = q[0] - p[0], q[1] - p[1]
    n = (dx ** 2 + dy ** 2) ** 0.5
    return (dx / n, dy / n) if n else (0.0, 0.0)


def edge(ax, points, label=None, label_seg=0, card_start=None, card_end=None,
         label_side=1, start_badge_at=None, end_badge_at=None):
    xs = [p[0] for p in points]
    ys = [p[1] for p in points]
    ax.plot(xs, ys, color=MAROON, lw=1.25, zorder=1, solid_capstyle="round")
    ax.annotate("", xy=points[-1], xytext=points[-2],
                arrowprops=dict(arrowstyle="-|>", color=MAROON, lw=1.25,
                                shrinkA=0, shrinkB=0, mutation_scale=9), zorder=2)

    def badge(point, direction, text, side, fixed=None):
        if fixed is not None:
            bx, by = fixed
        else:
            d = direction
            perp = (-d[1], d[0])
            bx = point[0] + d[0] * 0.20 + perp[0] * 0.15 * side
            by = point[1] + d[1] * 0.20 + perp[1] * 0.15 * side
        ax.text(bx, by, text, ha="center", va="center", fontsize=6.2,
                fontweight="bold", color=MAROON, zorder=6,
                bbox=dict(boxstyle="round,pad=0.13", fc=WHITE, ec=MAROON, lw=0.7))

    if card_start:
        d = unit(points[0], points[1])
        badge(points[0], d, card_start, +1, start_badge_at)
    if card_end:
        d = unit(points[-1], points[-2])
        badge(points[-1], d, card_end, -1, end_badge_at)

    if label:
        a, b = points[label_seg], points[label_seg + 1]
        mx, my = (a[0] + b[0]) / 2, (a[1] + b[1]) / 2
        d = unit(a, b)
        perp = (-d[1], d[0])
        lx = mx + perp[0] * 0.18 * label_side
        ly = my + perp[1] * 0.18 * label_side
        ax.text(lx, ly, label, ha="center", va="center", fontsize=6.2,
                style="italic", color=MUTED, zorder=5,
                bbox=dict(boxstyle="round,pad=0.12", fc=WHITE, ec="none"))


def build():
    fig, ax = plt.subplots(figsize=(16, 10), dpi=220)
    fig.patch.set_facecolor(WHITE)
    ax.set_xlim(0, 16)
    ax.set_ylim(0, 10)
    ax.axis("off")

    ax.text(0.55, 9.74, "AppTareas · Modelo entidad–relación",
            fontsize=19, fontweight="bold", color=MAROON_DARK, ha="left", va="center")
    ax.text(0.55, 9.44,
            "Propuesta: equipos con roles, proyectos, tareas, responsables múltiples y subtareas",
            fontsize=10, color=MUTED, ha="left", va="center")

    users = [
        ("account_id", "bigint", "PK"), ("name", "varchar(255)", ""),
        ("email", "varchar(255)", "UK"), ("password", "varchar(255)", ""),
        ("avatar_url ?", "varchar(255)", ""), ("timezone ?", "varchar(64)", ""),
        ("email_verified_at ?", "timestamptz", ""), ("timestamps", "created / updated", ""),
    ]
    teams = [
        ("team_id", "bigint", "PK"), ("name", "varchar(120)", ""),
        ("slug", "varchar(120)", "UK"), ("owner_id", "bigint", "FK"),
        ("archived_at ?", "timestamptz", ""), ("timestamps", "created / updated", ""),
    ]
    team_members = [
        ("team_id", "bigint", "PK,FK"), ("account_id", "bigint", "PK,FK"),
        ("role", "varchar(20)", ""), ("joined_at", "timestamptz", ""),
    ]
    projects = [
        ("project_id", "bigint", "PK"), ("team_id", "bigint", "FK"),
        ("name", "varchar(80)", ""), ("color ?", "varchar(7)", ""),
        ("archived_at ?", "timestamptz", ""), ("timestamps", "created / updated", ""),
    ]
    tasks = [
        ("task_id", "bigint", "PK"), ("team_id ?", "bigint", "FK"),
        ("project_id ?", "bigint", "FK"), ("created_by", "bigint", "FK"),
        ("title", "varchar(120)", ""), ("description ?", "text", ""),
        ("status", "varchar(20)", ""), ("priority", "varchar(20)", ""),
        ("start_date", "date", ""), ("end_date", "date", ""),
        ("completed_at ?", "timestamptz", ""), ("position", "integer", ""),
        ("deleted_at ?", "timestamptz", ""), ("timestamps", "created / updated", ""),
    ]
    assignees = [
        ("task_id", "bigint", "PK,FK"), ("account_id", "bigint", "PK,FK"),
        ("assigned_at", "timestamptz", ""),
    ]
    subtasks = [
        ("subtask_id", "bigint", "PK"), ("task_id", "bigint", "FK"),
        ("title", "varchar(160)", ""), ("is_done", "boolean", ""),
        ("position", "integer", ""), ("assignee_id ?", "bigint", "FK"),
        ("timestamps", "created / updated", ""),
    ]

    w_top = 2.90
    users_x, users_top = 0.45, 9.10
    members_x, members_top = 4.35, 9.10
    teams_x, teams_top = 8.25, 9.10
    projects_x, projects_top = 12.15, 9.10

    tasks_x, tasks_top, tasks_w = 4.55, 6.55, 4.10
    subtasks_x, subtasks_top, w_sub = 11.55, 6.40, 3.10
    assignees_x, assignees_top, w_ass = 7.75, 2.30, 3.10

    users_bottom = entity(ax, users_x, users_top, w_top, "ACCOUNT", users)
    members_bottom = entity(ax, members_x, members_top, w_top, "TEAM_MEMBER", team_members)
    teams_bottom = entity(ax, teams_x, teams_top, w_top, "TEAM", teams)
    projects_bottom = entity(ax, projects_x, projects_top, w_top, "PROJECT", projects)
    tasks_bottom = entity(ax, tasks_x, tasks_top, tasks_w, "TASK", tasks)
    subtasks_bottom = entity(ax, subtasks_x, subtasks_top, w_sub, "SUBTASK", subtasks)
    assignees_bottom = entity(ax, assignees_x, assignees_top, w_ass, "TASK_ASSIGNEE", assignees)

    # --- Relaciones ---
    edge(ax, [(users_x + w_top, 8.40), (members_x, 8.40)], None, 0, "1", "0..N")
    edge(ax, [(teams_x, 8.40), (members_x + w_top, 8.40)], None, 0, "1", "0..N")
    edge(ax, [(teams_x + w_top, 8.40), (projects_x, 8.40)], None, 0, "1", "0..N")

    edge(ax, [(teams_x + 0.05, teams_bottom), (teams_x + 0.05, tasks_top)],
         "posee", 0, "0..1", "0..N", label_side=-1,
         end_badge_at=(teams_x - 0.18, tasks_top + 0.20))

    edge(ax, [(projects_x + 1.45, projects_bottom), (projects_x + 1.45, 6.98),
              (8.60, 6.98), (8.60, tasks_top)],
         "contiene", 1, "0..1", "0..N", label_side=1,
         end_badge_at=(8.60 + 0.18, tasks_top + 0.20))

    edge(ax, [(users_x + 1.55, users_bottom), (users_x + 1.55, 5.55), (tasks_x, 5.55)],
         "crea", 1, "1", "0..N", label_side=1)

    edge(ax, [(users_x, 7.45), (0.20, 7.45), (0.20, 1.70), (assignees_x, 1.70)],
         "asigna", 2, "1", "0..N", label_side=1)

    edge(ax, [(tasks_x + 3.65, tasks_bottom), (tasks_x + 3.65, assignees_top)],
         None, 0, "1", "0..N")

    edge(ax, [(tasks_x + tasks_w, 4.80), (subtasks_x, 4.80)],
         "desglosa", 0, "1", "0..N", label_side=1)

    edge(ax, [(users_x + 2.55, users_bottom), (users_x + 2.55, 0.95),
              (13.30, 0.95), (13.30, subtasks_bottom)],
         "responsable de", 2, "0..1", "0..N", label_side=1)

    # --- Leyenda ---
    ly = 0.42
    def legend_badge(x, text, label):
        ax.text(x, ly, text, fontsize=6.2, fontweight="bold", color=MAROON,
                ha="center", va="center",
                bbox=dict(boxstyle="round,pad=0.16", fc=WHITE, ec=MAROON, lw=0.7))
        ax.text(x + 0.38, ly, label, fontsize=7.4, color=MUTED, va="center")

    legend_badge(0.62, "PK", "clave primaria")
    legend_badge(2.45, "FK", "clave foránea")
    legend_badge(4.20, "UK", "valor único")
    ax.text(6.00, ly, "1 ——▷ 0..N", fontsize=7.6, color=MAROON, va="center")
    ax.text(7.95, ly, "uno a muchos", fontsize=7.4, color=MUTED, va="center")
    ax.text(9.90, ly, "?", fontsize=7.6, color=MUTED, va="center", fontweight="bold")
    ax.text(10.10, ly, "campo opcional", fontsize=7.4, color=MUTED, va="center")

    ax.text(0.55, 0.16,
            "No se muestran las tablas de infraestructura de Laravel: sessions, personal_access_tokens, "
            "password_reset_tokens, cache, cache_locks, jobs, job_batches, failed_jobs y migrations.",
            fontsize=6.6, color=MUTED, va="center", ha="left")

    plt.tight_layout(pad=0.2)
    out_dir = os.path.dirname(os.path.abspath(__file__))
    os.makedirs(out_dir, exist_ok=True)
    fig.savefig(os.path.join(out_dir, "er-propuesto.png"), dpi=220,
                facecolor=WHITE, bbox_inches="tight", pad_inches=0.25)
    fig.savefig(os.path.join(out_dir, "er-propuesto.pdf"),
                facecolor=WHITE, bbox_inches="tight", pad_inches=0.25)
    fig.savefig(os.path.join(out_dir, "er-propuesto.svg"),
                facecolor=WHITE, bbox_inches="tight", pad_inches=0.25)
    plt.close(fig)
    print("OK")


if __name__ == "__main__":
    build()

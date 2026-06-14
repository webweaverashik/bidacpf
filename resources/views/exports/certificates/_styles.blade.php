<style>
    * { font-family: DejaVu Sans, sans-serif; }

    @page { margin: 110px 50px 90px 50px; }

    body { color: #1f2937; font-size: 11px; line-height: 1.6; }

    /* Fixed letterhead / footer (repeat on every page) */
    .letterhead {
        position: fixed; top: -85px; left: 0; right: 0;
        text-align: center; border-bottom: 1.5px solid #1f3a8a; padding-bottom: 6px;
    }
    .letterhead .org { font-size: 16px; font-weight: bold; color: #1f3a8a; }
    .letterhead .unit { font-size: 11px; color: #4b5563; }
    .letterhead .addr { font-size: 8px; color: #9ca3af; }

    .pagefoot {
        position: fixed; bottom: -70px; left: 0; right: 0;
        border-top: 0.5px solid #d1d5db; padding-top: 6px;
        font-size: 7.5px; color: #9ca3af; text-align: center;
    }

    .doc-meta { width: 100%; font-size: 9px; color: #6b7280; margin-bottom: 6px; }
    .doc-meta td { padding: 0; }
    .doc-meta .right { text-align: right; }

    .doc-title {
        text-align: center; font-size: 14px; font-weight: bold;
        text-transform: uppercase; letter-spacing: 0.5px;
        text-decoration: underline; margin: 6px 0 16px;
    }

    p { margin: 0 0 9px; text-align: justify; }
    .lead { margin-bottom: 12px; }

    .kv { width: 100%; border-collapse: collapse; margin: 10px 0 14px; }
    .kv td { padding: 4px 6px; border: 0.5px solid #d1d5db; }
    .kv td.label { background: #f9fafb; color: #4b5563; width: 32%; font-weight: bold; }

    table.grid { width: 100%; border-collapse: collapse; margin: 8px 0 14px; }
    table.grid th, table.grid td { border: 0.5px solid #d1d5db; padding: 4px 6px; font-size: 10px; }
    table.grid th { background: #f3f4f6; text-transform: uppercase; font-size: 8px; text-align: left; }
    table.grid td.num, table.grid th.num { text-align: right; }
    table.grid td.ctr, table.grid th.ctr { text-align: center; }
    table.grid tfoot td { background: #eef2ff; font-weight: bold; }

    .amount-box {
        border: 1px solid #1f3a8a; background: #f5f7ff; border-radius: 4px;
        padding: 8px 12px; margin: 12px 0; font-size: 13px; font-weight: bold;
        text-align: center; color: #1f3a8a;
    }

    .note { font-size: 9px; color: #6b7280; margin-top: 8px; }

    .sign-row { width: 100%; margin-top: 60px; border-collapse: collapse; }
    .sign-row td { width: 50%; vertical-align: bottom; font-size: 10px; }
    .sign-row .right { text-align: right; }
    .sign-line {
        border-top: 0.5px solid #1f2937; display: inline-block;
        min-width: 180px; padding-top: 3px; margin-top: 36px;
    }
    .muted { color: #6b7280; }
    .text-danger { color: #b91c1c; }
    .text-success { color: #15803d; }
</style>

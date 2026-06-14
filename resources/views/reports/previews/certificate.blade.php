{{--
    On-screen preview of a certificate.

    The certificate is a self-contained HTML document (its own <style> block using
    DejaVu Sans, the same markup DomPDF renders). To keep those styles from
    leaking into — or being overridden by — the Metronic page, the rendered
    document is dropped into an <iframe srcdoc="…"> so it paints in full
    isolation, exactly as it will in the PDF.

    $doc    : fully-rendered certificate HTML (string)
    $report : the report definition (key, label, formats, ...)
--}}
@php
    $isPdfOnly = ($report['formats'] ?? ['pdf']) === ['pdf'];
@endphp

<div class="rpt-preview rpt-certificate"
    data-preview-title="{{ $report['label'] ?? 'Certificate' }}"
    data-preview-subtitle="Preview — use Download for the final PDF.">

    <div class="rpt-cert-paper">
        <iframe class="rpt-cert-frame" title="{{ $report['label'] ?? 'Certificate' }} preview"
            srcdoc="{{ $doc }}"></iframe>
    </div>

    <div class="notice d-flex bg-light-primary rounded border-primary border border-dashed p-4 mt-4">
        <i class="ki-outline ki-information fs-2 text-primary me-3"></i>
        <div class="fs-7">
            This is an on-screen preview. Click
            <span class="fw-semibold">Download&nbsp;PDF</span>
            @unless ($isPdfOnly)
                (or <span class="fw-semibold">Excel</span>)
            @endunless
            to generate the official document.
        </div>
    </div>
</div>

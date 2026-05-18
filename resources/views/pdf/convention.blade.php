<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Convention de Stage</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            background: #f4f6f9;
            color: #1a2340;
            font-size: 11px;
            line-height: 1.7;
        }

        .page {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            background: #ffffff;
            position: relative;
            overflow: hidden;
        }

        /* ── Top accent strip ── */
        .top-strip {
            background: #1a3066;
            height: 8px;
            width: 100%;
        }

        .header-bar {
            background: #1a3066;
            padding: 22px 48px 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            color: #ffffff;
        }

        .header-left {
            flex: 1;
        }

        .univ-name {
            font-size: 15px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            line-height: 1.3;
        }

        .univ-sub {
            font-size: 10px;
            color: #a8bfe0;
            margin-top: 3px;
            letter-spacing: 0.3px;
        }

        .header-logo {
            flex-shrink: 0;
            margin-left: 24px;
        }

        .header-logo img {
            height: 65px;
            width: auto;
            display: block;
            filter: brightness(0) invert(1);
        }

        .logo-placeholder {
            width: 65px;
            height: 65px;
            border: 1.5px solid rgba(255,255,255,0.3);
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 9px;
            color: rgba(255,255,255,0.5);
            text-align: center;
        }

        /* ── Document title band ── */
        .title-band {
            background: #f0f4fa;
            border-bottom: 2px solid #1a3066;
            border-top: 1px solid #dce4ef;
            padding: 16px 48px;
            text-align: center;
        }

        .doc-type {
            font-size: 9px;
            letter-spacing: 3px;
            text-transform: uppercase;
            color: #8a9ab5;
            margin-bottom: 4px;
        }

        .doc-title {
            font-size: 22px;
            font-weight: bold;
            color: #1a3066;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .doc-title-fr {
            font-size: 12px;
            color: #5a6a80;
            margin-top: 3px;
            letter-spacing: 1px;
        }

        /* ── Main content ── */
        .content {
            padding: 28px 48px 36px;
        }

        /* ── Section ── */
        .section {
            margin-bottom: 22px;
        }

        .section-header {
            display: flex;
            align-items: center;
            margin-bottom: 12px;
            gap: 10px;
        }

        .section-number {
            background: #1a3066;
            color: #ffffff;
            font-size: 10px;
            font-weight: bold;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .section-title {
            font-size: 12px;
            font-weight: bold;
            color: #1a3066;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            border-bottom: 1.5px solid #1a3066;
            flex: 1;
            padding-bottom: 3px;
        }

        /* ── Info Table ── */
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4px;
        }

        .info-table td {
            padding: 7px 12px;
            border: 1px solid #dce4ef;
            vertical-align: top;
        }

        .info-table tr:nth-child(even) td {
            background: #f7f9fc;
        }

        .info-table .label-cell {
            width: 38%;
            color: #5a6a80;
            font-weight: bold;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-table .value-cell {
            color: #1a2340;
            font-size: 11px;
        }

        /* ── Two-column layout ── */
        .two-col {
            display: table;
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 22px;
            gap: 16px;
        }

        .col {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding-right: 12px;
        }

        .col:last-child {
            padding-right: 0;
            padding-left: 12px;
        }

        /* ── Obligations box ── */
        .obligation-box {
            background: #f7f9fc;
            border-left: 3px solid #1a3066;
            padding: 12px 16px;
            margin-bottom: 8px;
        }

        .obligation-box p {
            font-size: 10.5px;
            color: #3a4a60;
            margin-bottom: 5px;
        }

        .obligation-box p:last-child { margin-bottom: 0; }

        .bullet {
            display: inline-block;
            width: 5px;
            height: 5px;
            background: #1a3066;
            border-radius: 50%;
            margin-right: 7px;
            vertical-align: middle;
            flex-shrink: 0;
        }

        /* ── Legal clause ── */
        .legal-text {
            font-size: 10px;
            color: #5a6a80;
            line-height: 1.7;
            text-align: justify;
            background: #fafbfd;
            border: 1px solid #dce4ef;
            padding: 14px 18px;
            margin-bottom: 22px;
        }

        /* ── Signature Section ── */
        .signature-section {
            margin-top: 30px;
        }

        .sig-header {
            font-size: 9px;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: #8a9ab5;
            text-align: center;
            margin-bottom: 18px;
            border-top: 1px solid #dce4ef;
            padding-top: 14px;
        }

        .sig-grid {
            display: table;
            width: 100%;
            border-collapse: collapse;
        }

        .sig-box {
            display: table-cell;
            width: 33.33%;
            text-align: center;
            padding: 0 12px;
            vertical-align: bottom;
        }

        .sig-stamp {
            width: 80px;
            height: 80px;
            border: 2px dashed #b0bccc;
            border-radius: 50%;
            margin: 0 auto 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 7.5px;
            color: #b0bccc;
            text-align: center;
        }

        .sig-line {
            border-top: 1.5px solid #1a3066;
            margin: 30px auto 6px;
            width: 85%;
        }

        .sig-label {
            font-size: 10px;
            font-weight: bold;
            color: #1a2340;
        }

        .sig-sub {
            font-size: 9px;
            color: #8a9ab5;
            margin-top: 2px;
        }

        /* ── Footer ── */
        .footer {
            position: relative;
            margin-top: 28px;
            padding: 12px 48px;
            border-top: 2px solid #1a3066;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .footer-text {
            font-size: 8.5px;
            color: #8a9ab5;
        }

        .footer-ref {
            font-size: 8px;
            color: #b0bccc;
        }

        .bottom-strip {
            background: #1a3066;
            height: 6px;
            width: 100%;
        }

        .divider {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 18px 0;
        }
        .divider-line { flex: 1; height: 1px; background: #dce4ef; }
        .divider-dot { width: 5px; height: 5px; background: #1a3066; border-radius: 50%; }

        @media print {
            body { background: white; }
            .page { margin: 0; box-shadow: none; }
        }
    </style>
</head>
<body>
<div class="page">

    <!-- Header -->
    <div class="header-bar">
        <div class="header-left">
            <div class="univ-name">{{ $uni['name'] }}</div>
            <div class="univ-sub">{{ $uni['faculty'] }}</div>
            <div class="univ-sub">{{ $uni['department'] }}</div>
        </div>
        <div class="header-logo">
            @if($uni['logo'])
                <img src="{{ $uni['logo'] }}" alt="Logo">
            @else
                <div class="logo-placeholder">LOGO</div>
            @endif
        </div>
    </div>

    <!-- Title -->
    <div class="title-band">
        <div class="doc-type">Document Officiel &mdash; Official Document</div>
        <div class="doc-title">Convention de Stage</div>
        <div class="doc-title-fr">Internship Agreement</div>
    </div>

    <div class="content">

        <!-- Preamble -->
        <div class="legal-text">
            La présente convention est conclue entre les parties soussignées en vue d'organiser
            un stage professionnel conformément à la réglementation en vigueur relative aux stages
            en milieu professionnel. Elle définit les droits et obligations de chaque partie pour
            la durée du stage mentionnée ci-dessous.
            <br><br>
            This agreement is entered into between the undersigned parties for the purpose of
            organizing a professional internship in accordance with applicable regulations. It
            defines the rights and obligations of each party for the duration of the internship
            specified herein.
        </div>

        <!-- Section 1 — Parties -->
        <div class="two-col">
            <div class="col">
                <div class="section">
                    <div class="section-header">
                        <div class="section-number">1</div>
                        <div class="section-title">Établissement d'Accueil</div>
                    </div>
                    <table class="info-table">
                        <tr>
                            <td class="label-cell">Entreprise</td>
                            <td class="value-cell">{{ optional($internship->company)->name }}</td>
                        </tr>
                        <tr>
                            <td class="label-cell">Secteur</td>
                            <td class="value-cell">{{ optional($internship->company)->sector ?? '—' }}</td>
                        </tr>
                        <tr>
                            <td class="label-cell">Adresse</td>
                            <td class="value-cell">{{ optional($internship->company)->address ?? '—' }}</td>
                        </tr>
                        <tr>
                            <td class="label-cell">Représentant</td>
                            <td class="value-cell">{{ optional($internship->company)->contact_name ?? '—' }}</td>
                        </tr>
                    </table>
                </div>
            </div>
            <div class="col">
                <div class="section">
                    <div class="section-header">
                        <div class="section-number">2</div>
                        <div class="section-title">Informations Étudiant</div>
                    </div>
                    <table class="info-table">
                        <tr>
                            <td class="label-cell">Nom complet</td>
                            <td class="value-cell">{{ optional($internship->student->user)->name }}</td>
                        </tr>
                        <tr>
                            <td class="label-cell">Filière</td>
                            <td class="value-cell">{{ optional($internship->student)->specialty ?? '—' }}</td>
                        </tr>
                        <tr>
                            <td class="label-cell">Niveau</td>
                            <td class="value-cell">{{ optional($internship->student)->level ?? '—' }}</td>
                        </tr>
                        <tr>
                            <td class="label-cell">N° Matricule</td>
                            <td class="value-cell">{{ optional($internship->student)->student_id ?? '—' }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- Section 3 — Internship Details -->
        <div class="section">
            <div class="section-header">
                <div class="section-number">3</div>
                <div class="section-title">Objet et Conditions du Stage</div>
            </div>
            <table class="info-table">
                <tr>
                    <td class="label-cell">Intitulé du poste</td>
                    <td class="value-cell">{{ optional($internship->offer)->title }}</td>
                </tr>
                <tr>
                    <td class="label-cell">Description</td>
                    <td class="value-cell">{{ optional($internship->offer)->description ?? 'Stage professionnel dans le cadre de la formation universitaire.' }}</td>
                </tr>
                <tr>
                    <td class="label-cell">Date de début</td>
                    <td class="value-cell">{{ optional($internship->offer)->start_date ? \Carbon\Carbon::parse($internship->offer->start_date)->format('d / m / Y') : '—' }}</td>
                </tr>
                <tr>
                    <td class="label-cell">Date de fin</td>
                    <td class="value-cell">{{ optional($internship->offer)->end_date ? \Carbon\Carbon::parse($internship->offer->end_date)->format('d / m / Y') : '—' }}</td>
                </tr>
                <tr>
                    <td class="label-cell">Gratification</td>
                    <td class="value-cell">{{ optional($internship->offer)->salary ?? 'Non rémunéré / Unpaid' }}</td>
                </tr>
                <tr>
                    <td class="label-cell">Date de signature</td>
                    <td class="value-cell">{{ now()->format('d / m / Y') }}</td>
                </tr>
            </table>
        </div>

        <!-- Section 4 — Obligations -->
        <div class="two-col">
            <div class="col">
                <div class="section">
                    <div class="section-header">
                        <div class="section-number">4</div>
                        <div class="section-title">Obligations de l'Entreprise</div>
                    </div>
                    <div class="obligation-box">
                        <p><span class="bullet"></span>Accueillir le stagiaire dans les meilleures conditions.</p>
                        <p><span class="bullet"></span>Désigner un tuteur professionnel qualifié.</p>
                        <p><span class="bullet"></span>Assurer la confidentialité des données du stagiaire.</p>
                        <p><span class="bullet"></span>Fournir les équipements nécessaires à la mission.</p>
                        <p><span class="bullet"></span>Délivrer une attestation en fin de stage.</p>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="section">
                    <div class="section-header">
                        <div class="section-number">5</div>
                        <div class="section-title">Obligations de l'Étudiant</div>
                    </div>
                    <div class="obligation-box">
                        <p><span class="bullet"></span>Respecter le règlement intérieur de l'entreprise.</p>
                        <p><span class="bullet"></span>Faire preuve de ponctualité et de professionnalisme.</p>
                        <p><span class="bullet"></span>Maintenir la confidentialité des informations.</p>
                        <p><span class="bullet"></span>Rédiger un rapport de stage à l'issue du stage.</p>
                        <p><span class="bullet"></span>Informer l'encadrant en cas d'absence justifiée.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Legal -->
        <div class="legal-text">
            La présente convention est établie en trois (03) exemplaires originaux, dont un pour
            chaque partie signataire. Toute modification devra faire l'objet d'un avenant signé
            par les trois parties. En cas de litige, les parties s'engagent à rechercher une
            solution amiable avant tout recours judiciaire.
        </div>

        <!-- Signatures -->
        <div class="signature-section">
            <div class="sig-header">Signatures des Parties — Lu et Approuvé</div>
            <div class="sig-grid">
                <div class="sig-box">
                    <div class="sig-stamp">Cachet<br>Université</div>
                    <div class="sig-line"></div>
                    <div class="sig-label">Le Responsable Pédagogique</div>
                    <div class="sig-sub">{{ $uni['name'] }}</div>
                </div>
                <div class="sig-box">
                    <div class="sig-stamp">Cachet<br>Département</div>
                    <div class="sig-line"></div>
                    <div class="sig-label">Le Chef de Département</div>
                    <div class="sig-sub">{{ $uni['department'] }}</div>
                </div>
                <div class="sig-box">
                    <div class="sig-stamp">Cachet<br>Entreprise</div>
                    <div class="sig-line"></div>
                    <div class="sig-label">Le Responsable de Stage</div>
                    <div class="sig-sub">{{ optional($internship->company)->name }}</div>
                </div>
            </div>
        </div>

    </div><!-- .content -->

    <!-- Footer -->
    <div class="footer">
        <div class="footer-text">
            Convention établie le {{ now()->format('d F Y') }} &nbsp;|&nbsp; Plateforme de Gestion des Stages
        </div>
        <div class="footer-ref">
            Réf&nbsp;: CONV-{{ now()->format('Y') }}-{{ str_pad($internship->id ?? '0000', 4, '0', STR_PAD_LEFT) }}
        </div>
    </div>

    <div class="bottom-strip"></div>

</div><!-- .page -->
</body>
</html>
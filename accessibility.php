<?php
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'Accessibility Statement';
require __DIR__ . '/includes/header.php';
?>
<div class="wrap section" style="max-width:760px">
  <h1>Accessibility statement</h1>
  <p class="muted">We want everyone in Thornaby to be able to order dinner.</p>

  <h2>What we’ve done</h2>
  <ul class="stack" style="padding-left:1.1rem">
    <li>Semantic HTML5 landmarks (<code>header</code>, <code>nav</code>,
      <code>main</code>, <code>footer</code>) and a skip-to-content link.</li>
    <li>Keyboard-operable throughout — including the pizza builder, which
      uses <code>role="button"</code>, <code>tabindex</code> and
      <code>aria-pressed</code> on every topping.</li>
    <li><code>aria-live</code> regions announce cart updates and postcode
      results to screen readers.</li>
    <li>Visible focus styles, colour contrast checked against WCAG AA, and a
      <code>prefers-reduced-motion</code> rule that disables animation.</li>
    <li>A full dark mode that respects the OS setting.</li>
    <li>All decorative SVG art is marked <code>aria-hidden</code>; meaningful
      illustrations have descriptive <code>aria-label</code>s.</li>
  </ul>

  <h2>Testing</h2>
  <p>Pages are checked with the WAVE evaluation tool and validated against the
    W3C HTML5 and CSS3 validators. Screenshots of the results are included in
    the project ReadMe as required by the brief.</p>

  <h2>Feedback</h2>
  <p>Found a barrier? Tell us via the <a href="contact.php">contact form</a>
    and we’ll fix it.</p>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>

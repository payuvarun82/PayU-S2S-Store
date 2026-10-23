#!/usr/bin/env node
const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');

const base = __dirname;
const mmd = path.join(base, 'diy-merchant-integration.mmd');
const svg = path.join(base, 'diy-merchant-integration.svg');
const png = path.join(base, 'diy-merchant-integration.png');
const shot = path.join(base, '.screenshot.js');

execSync(`npx -y @mermaid-js/mermaid-cli -i "${mmd}" -o "${svg}" -b '#1e1e1e' -w 1600 -H 1600`, {
  stdio: 'inherit',
  cwd: base,
});

let svgText = fs.readFileSync(svg, 'utf8');

const labelMap = {
  L_Issues_Live_0: 'NO',
  L_Issues_FAQ_0: 'YES',
  L_Resolved_Live_0: 'YES',
  L_Resolved_Support_0: 'NO',
};

const positions = [];
for (const [edgeId, label] of Object.entries(labelMap)) {
  const groupRe = new RegExp(
    `<g class="edgeLabel" transform="translate\\(([^)]+)\\)">\\s*<g class="label" data-id="${edgeId}"`,
    'm'
  );
  const m = svgText.match(groupRe);
  if (!m) {
    console.error('missing edge group', edgeId);
    continue;
  }
  const [x, y] = m[1].split(',').map((v) => parseFloat(v.trim()));
  positions.push({ x, y, label });
}

svgText = svgText.replace(
  '</style>',
  '#my-svg .edgeLabel{display:none !important;}</style>'
);

const labelsSvg = positions
  .map(({ x, y, label }) => {
    const width = label === 'YES' ? 42 : 34;
    const height = 22;
    return `<g transform="translate(${x}, ${y})">
      <rect x="${-width / 2}" y="${-height / 2}" width="${width}" height="${height}" rx="4" fill="#ffffff" stroke="#333333" stroke-width="1"/>
      <text text-anchor="middle" dominant-baseline="middle" fill="#111111" font-family="Arial, sans-serif" font-size="13" font-weight="700">${label}</text>
    </g>`;
  })
  .join('');

svgText = svgText.replace('</svg>', `<g id="decision-labels">${labelsSvg}</g></svg>`);
fs.writeFileSync(svg, svgText);

fs.writeFileSync(
  shot,
  `const puppeteer = require('puppeteer');
(async () => {
  const browser = await puppeteer.launch({ headless: 'new' });
  const page = await browser.newPage();
  await page.setViewport({ width: 1600, height: 1600, deviceScaleFactor: 2 });
  await page.goto('file://${svg}', { waitUntil: 'networkidle0' });
  const el = await page.$('#my-svg');
  if (!el) throw new Error('SVG element not found');
  await el.screenshot({ path: '${png}' });
  await browser.close();
})();`
);

execSync(`npx -y -p puppeteer node "${shot}"`, { stdio: 'inherit', cwd: base });
fs.unlinkSync(shot);

const edges = [...svgText.matchAll(/data-id="(L_Prod[^"]+)"/g)].map((m) => m[1]);
console.log('Prod edges:', [...new Set(edges)].join(', ') || 'none');
console.log('Rendered', png);

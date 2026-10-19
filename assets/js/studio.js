/* =========================================================
   PISHGAM NOOR — Light Studio
   Procedural 3D product viewer (Three.js)
   ========================================================= */

import * as THREE from 'three';
import { OrbitControls } from 'three/addons/controls/OrbitControls.js';
import { RoundedBoxGeometry } from 'three/addons/geometries/RoundedBoxGeometry.js';
import { RoomEnvironment } from 'three/addons/environments/RoomEnvironment.js';
import { EffectComposer } from 'three/addons/postprocessing/EffectComposer.js';
import { RenderPass } from 'three/addons/postprocessing/RenderPass.js';
import { UnrealBloomPass } from 'three/addons/postprocessing/UnrealBloomPass.js';
import { OutputPass } from 'three/addons/postprocessing/OutputPass.js';

/* ---------------- Utilities ---------------- */

const $ = (s, c = document) => c.querySelector(s);
const $$ = (s, c = document) => Array.from(c.querySelectorAll(s));
const clamp = (n, a, b) => Math.min(b, Math.max(a, n));
const faNum = (n) => new Intl.NumberFormat('fa-IR', { maximumFractionDigits: 1 }).format(n);
const faPlain = (n) => new Intl.NumberFormat('fa-IR', { useGrouping: false, maximumFractionDigits: 1 }).format(n);
const isMobile = window.matchMedia('(max-width: 900px)').matches;
const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

function kelvinToColor(k) {
  const t = k / 100;
  let r, g, b;
  if (t <= 66) {
    r = 255;
    g = 99.4708025861 * Math.log(t) - 161.1195681661;
  } else {
    r = 329.698727446 * Math.pow(t - 60, -0.1332047592);
    g = 288.1221695283 * Math.pow(t - 60, -0.0755148492);
  }
  if (t >= 66) b = 255;
  else if (t <= 19) b = 0;
  else b = 138.5177312231 * Math.log(t - 10) - 305.0447927307;
  return new THREE.Color(
    clamp(r, 0, 255) / 255,
    clamp(g, 0, 255) / 255,
    clamp(b, 0, 255) / 255
  );
}

const CCT_HEX = {
  3000: '#ffb46b',
  4000: '#ffd9b0',
  5000: '#fff1dd',
  6000: '#eaf2ff'
};

/* ---------------- Textures ---------------- */

let glowTex;
let shadowTex;

function makeGlowTexture() {
  if (glowTex) return glowTex;
  const c = document.createElement('canvas');
  c.width = c.height = 256;
  const ctx = c.getContext('2d');
  const g = ctx.createRadialGradient(128, 128, 0, 128, 128, 128);
  g.addColorStop(0, 'rgba(255,255,255,1)');
  g.addColorStop(0.3, 'rgba(255,255,255,.72)');
  g.addColorStop(0.62, 'rgba(255,255,255,.22)');
  g.addColorStop(1, 'rgba(255,255,255,0)');
  ctx.fillStyle = g;
  ctx.fillRect(0, 0, 256, 256);
  glowTex = new THREE.CanvasTexture(c);
  glowTex.colorSpace = THREE.SRGBColorSpace;
  return glowTex;
}

function makeShadowTexture() {
  if (shadowTex) return shadowTex;
  const c = document.createElement('canvas');
  c.width = c.height = 256;
  const ctx = c.getContext('2d');
  const g = ctx.createRadialGradient(128, 128, 8, 128, 128, 128);
  g.addColorStop(0, 'rgba(0,0,0,.72)');
  g.addColorStop(0.48, 'rgba(0,0,0,.34)');
  g.addColorStop(1, 'rgba(0,0,0,0)');
  ctx.fillStyle = g;
  ctx.fillRect(0, 0, 256, 256);
  shadowTex = new THREE.CanvasTexture(c);
  return shadowTex;
}

let woodTex;
function makeWoodTexture() {
  if (woodTex) return woodTex;
  const c = document.createElement('canvas');
  c.width = c.height = 512;
  const ctx = c.getContext('2d');
  const base = ctx.createLinearGradient(0, 0, 512, 0);
  base.addColorStop(0, '#a18160');
  base.addColorStop(0.5, '#b89772');
  base.addColorStop(1, '#947252');
  ctx.fillStyle = base;
  ctx.fillRect(0, 0, 512, 512);

  // vertical slats with a thin shadow gap
  for (let x = 0; x < 512; x += 64) {
    ctx.fillStyle = 'rgba(255,255,255,.08)';
    ctx.fillRect(x + 2, 0, 1, 512);
    ctx.fillStyle = 'rgba(0,0,0,.22)';
    ctx.fillRect(x + 4, 0, 2, 512);
    ctx.fillStyle = 'rgba(255,255,255,.035)';
    ctx.fillRect(x + 10, 0, 18, 512);
  }
  // subtle grain
  for (let i = 0; i < 90; i += 1) {
    const x = Math.random() * 512;
    const y = Math.random() * 512;
    ctx.strokeStyle = `rgba(60,38,24,${0.03 + Math.random() * 0.05})`;
    ctx.beginPath();
    ctx.moveTo(x, y);
    ctx.bezierCurveTo(x + 9, y + 18, x - 8, y + 42, x + 4, y + 66);
    ctx.stroke();
  }

  woodTex = new THREE.CanvasTexture(c);
  woodTex.colorSpace = THREE.SRGBColorSpace;
  woodTex.wrapS = THREE.RepeatWrapping;
  woodTex.wrapT = THREE.RepeatWrapping;
  woodTex.repeat.set(2, 1);
  return woodTex;
}

/* ---------------- Materials ---------------- */

function bodyMaterial(color = '#16171c', metalness = 0.62, roughness = 0.38) {
  return new THREE.MeshStandardMaterial({
    color,
    metalness,
    roughness,
    envMapIntensity: 0.55
  });
}

function ledMaterial(color = '#ffb46b', intensity = 2.6) {
  return new THREE.MeshStandardMaterial({
    color: 0xffffff,
    emissive: new THREE.Color(color),
    emissiveIntensity: intensity,
    metalness: 0.05,
    roughness: 0.34,
    envMapIntensity: 0.2
  });
}

function darkStageMaterial(color = '#171b22', roughness = 0.82) {
  return new THREE.MeshStandardMaterial({
    color,
    metalness: 0.18,
    roughness,
    envMapIntensity: 0.35
  });
}

/* ---------------- Geometry helpers ---------------- */

function rbox(w, h, d, radius, mat) {
  return new THREE.Mesh(new RoundedBoxGeometry(w, h, d, 3, radius), mat);
}

function box(w, h, d, mat) {
  return new THREE.Mesh(new THREE.BoxGeometry(w, h, d), mat);
}

function cylinder(rt, rb, h, seg, mat) {
  return new THREE.Mesh(new THREE.CylinderGeometry(rt, rb, h, seg), mat);
}

function makeGroup() {
  const g = new THREE.Group();
  g.userData = { bodyMeshes: [], ledMeshes: [], lights: [], glows: [] };
  return g;
}

function addBody(g, mesh) {
  g.userData.bodyMeshes.push(mesh);
  return mesh;
}
function addLed(g, mesh, base = 2.6) {
  mesh.userData.baseEmissive = base;
  g.userData.ledMeshes.push(mesh);
  return mesh;
}
function addLight(g, light, base) {
  light.userData.baseIntensity = base;
  g.userData.lights.push({ light, base });
  return light;
}
function addGlow(g, mesh, base) {
  mesh.userData.baseOpacity = base;
  g.userData.glows.push({ mesh, base });
  return mesh;
}

function floorGlow(w, d, color, opacity) {
  const mat = new THREE.MeshBasicMaterial({
    map: makeGlowTexture(),
    color,
    transparent: true,
    opacity,
    blending: THREE.AdditiveBlending,
    depthWrite: false,
    side: THREE.DoubleSide
  });
  const mesh = new THREE.Mesh(new THREE.PlaneGeometry(w, d), mat);
  mesh.rotation.x = -Math.PI / 2;
  mesh.position.y = 0.013;
  return mesh;
}

function contactShadow(w, d, opacity = 0.65) {
  const mat = new THREE.MeshBasicMaterial({
    map: makeShadowTexture(),
    transparent: true,
    opacity,
    depthWrite: false
  });
  const mesh = new THREE.Mesh(new THREE.PlaneGeometry(w, d), mat);
  mesh.rotation.x = -Math.PI / 2;
  mesh.position.y = 0.008;
  return mesh;
}

function glowSprite(color, opacity, sx, sy) {
  const mat = new THREE.SpriteMaterial({
    map: makeGlowTexture(),
    color,
    transparent: true,
    opacity,
    blending: THREE.AdditiveBlending,
    depthWrite: false
  });
  const sprite = new THREE.Sprite(mat);
  sprite.scale.set(sx, sy, 1);
  return sprite;
}

function makeCable(from, to, radius, mat) {
  const dir = new THREE.Vector3().subVectors(to, from);
  const len = dir.length();
  const mesh = new THREE.Mesh(new THREE.CylinderGeometry(radius, radius, len, 6), mat);
  mesh.position.copy(from).addScaledVector(dir, 0.5);
  mesh.quaternion.setFromUnitVectors(new THREE.Vector3(0, 1, 0), dir.clone().normalize());
  return mesh;
}

function crescentShape(inner, outer, start, end) {
  const s = new THREE.Shape();
  s.absarc(0, 0, outer, start, end, false);
  s.absarc(0, 0, inner, end, start, true);
  s.closePath();
  return s;
}

/* ---------------- Product data ---------------- */

const INDOOR_FINISHES = [
  { id: 'black', label: 'مشکی مات', color: '#1b1c22', metalness: 0.34, roughness: 0.56 },
  { id: 'white', label: 'سفید مات', color: '#e9e5db', metalness: 0.14, roughness: 0.62 },
  { id: 'graphite', label: 'گرافیتی', color: '#4a4d53', metalness: 0.46, roughness: 0.44 }
];

const OUTDOOR_FINISHES = [
  { id: 'black', label: 'مشکی مات', color: '#1b1c22', metalness: 0.3, roughness: 0.58 },
  { id: 'graphite', label: 'گرافیتی', color: '#42464c', metalness: 0.44, roughness: 0.46 },
  { id: 'bronze', label: 'برنز تیره', color: '#5c4433', metalness: 0.38, roughness: 0.5 }
];

const PRODUCTS = {
  gamma: {
    id: 'gamma',
    letter: 'Γ',
    family: 'خانواده گاما',
    name: 'چراغ خطی کنافی گاما',
    short: 'گاما / خطی کنافی',
    desc: 'خط نوری یکپارچه در سقف کناف؛ بدون قطع، بدون خیرگی.',
    image: 'assets/img/products/gamma-kd.webp',
    url: 'https://pishgamnoor.com/products/gamma-kd/',
    stage: 'indoor',
    finishes: INDOOR_FINISHES,
    ccts: [3000, 4000, 5000, 6000],
    specs: [
      ['توان', '۲۵ وات / متر'],
      ['شار نوری', '۲۵۰۰ لومن'],
      ['CRI', 'بالای ۹۰'],
      ['درجه حفاظت', 'IP43'],
      ['مقطع پروفیل', '۳۰.۹ × ۱۳ mm'],
      ['نوع نصب', 'کنافی'],
      ['سیستم هوشمند', 'Zigbee / KNX'],
      ['گارانتی', '۳ تا ۶ سال']
    ],
    camera: { pos: [3.4, 1.55, 4.6], target: [0, 2.0, 0], min: 3.2, max: 11, polarMax: 1.9 },
    presets: {
      three: [3.4, 1.55, 4.6],
      front: [4.6, 1.55, 0],
      side: [0, 1.55, 4.6],
      detail: [2.5, 1.7, 3.3]
    },
    build: buildGamma
  },
  theta: {
    id: 'theta',
    letter: 'Θ',
    family: 'خانواده تتا',
    name: 'چراغ خطی کرو تتا C',
    short: 'تتا / خطی منحنی',
    desc: 'فرم منحنی؛ نور به‌عنوان یک حجم پیوسته.',
    image: 'assets/img/products/theta-c.webp',
    url: 'https://pishgamnoor.com/products/theta-c-decorative/',
    stage: 'indoor',
    finishes: INDOOR_FINISHES,
    ccts: [3000, 4000, 5000, 6000],
    specs: [
      ['توان', '۲۷ تا ۵۴ وات / متر'],
      ['شار نوری', '۵۲۰۰ تا ۲۲۶۰۰ لومن'],
      ['CRI', 'بالای ۹۰'],
      ['درجه حفاظت', 'IP43'],
      ['مقطع پروفیل', '۶۰ تا ۱۵۰ × ۶۷ mm'],
      ['نوع نصب', 'آویز / توکار'],
      ['سیستم هوشمند', 'DALI 2'],
      ['گارانتی', '۳ تا ۶ سال']
    ],
    camera: { pos: [1.2, 1.8, 7.4], target: [0.05, 2.5, 0.3], min: 3.6, max: 13, polarMax: 1.9 },
    presets: {
      three: [1.2, 1.8, 7.4],
      front: [0, 1.8, 7.8],
      side: [7.8, 1.8, 0],
      detail: [0.8, 1.6, 4.8]
    },
    build: buildTheta
  },
  omega: {
    id: 'omega',
    letter: 'Ω',
    family: 'خانواده امگا',
    name: 'چراغ خطی امگا ایستاده',
    short: 'امگا / خطی ایستاده',
    desc: 'یک خط ایستاده؛ نور از درون بدنه به فضا می‌ریزد.',
    image: 'assets/img/products/omega-stand.webp',
    url: 'https://pishgamnoor.com/products/omega-stand/',
    stage: 'indoor',
    finishes: INDOOR_FINISHES,
    ccts: [3000, 4000, 5000, 6000],
    specs: [
      ['توان', '۱۰ تا ۲۰ وات / متر'],
      ['شار نوری', '۱۰۰۰ تا ۲۰۰۰ لومن'],
      ['CRI', 'بالای ۹۰'],
      ['درجه حفاظت', 'IP43'],
      ['مقطع پروفیل', '۱۶ mm'],
      ['نوع نصب', 'روکار / آویز'],
      ['سیستم هوشمند', 'Zigbee / KNX'],
      ['گارانتی', '۳ تا ۶ سال']
    ],
    camera: { pos: [-3.8, 1.9, 4.6], target: [0, 1.15, 0], min: 2.6, max: 11, polarMax: 1.75 },
    presets: {
      three: [-3.8, 1.9, 4.6],
      front: [-6.0, 1.15, 0],
      side: [0, 1.15, 6.0],
      detail: [-2.6, 1.5, 3.4]
    },
    build: buildOmega
  },
  zeous: {
    id: 'zeous',
    letter: 'Ζ',
    family: 'خانواده زئوس',
    name: 'چراغ ایستاده محوطه زئوس ۲',
    short: 'زئوس / محوطه',
    desc: 'فرم درخت‌مانند؛ دو بازو، دو لایه نور برای مسیر و محوطه.',
    image: 'assets/img/products/zeous-2.webp',
    url: 'https://pishgamnoor.com/products/zeous-2/',
    stage: 'outdoor',
    finishes: OUTDOOR_FINISHES,
    ccts: [3000, 4000, 5000],
    specs: [
      ['توان', '۱۷ تا ۳۴ وات'],
      ['شار نوری', '۲۳۰۰ تا ۴۶۰۰ لومن'],
      ['CRI', 'بالای ۹۰'],
      ['درجه حفاظت', 'IP65'],
      ['ارتفاع', '۲۰۰ تا ۲۵۰ سانتی‌متر'],
      ['نوع نصب', 'روکار / رول‌بولت'],
      ['سیستم هوشمند', 'DALI 2 / KNX'],
      ['گارانتی', '۳ سال']
    ],
    camera: { pos: [3.8, 2.3, 5.4], target: [0, 1.5, 0], min: 3.0, max: 13, polarMax: 1.7 },
    presets: {
      three: [3.8, 2.3, 5.4],
      front: [5.8, 1.5, 0],
      side: [0, 1.5, 6.2],
      detail: [2.7, 1.8, 3.8]
    },
    build: buildZeous
  },
  magnet: {
    id: 'magnet',
    letter: 'Μ',
    family: 'خانواده مگنت',
    name: 'چراغ مگنتی لنزی ۲۴',
    short: 'مگنت / لنزی ۲۴',
    desc: '۲۴ لنز در یک پروفیل باریک؛ نور دقیق و قابل جابه‌جایی.',
    image: 'assets/img/products/magnet-lenz-24.webp',
    url: 'https://pishgamnoor.com/products/magnet-lenz-24/',
    stage: 'indoor',
    finishes: INDOOR_FINISHES,
    ccts: [3000, 4000],
    specs: [
      ['توان', '۲۴ وات / متر'],
      ['شار نوری', '۲۴۰۰ لومن'],
      ['CRI', 'بالای ۹۰'],
      ['درجه حفاظت', 'IP44'],
      ['تعداد لنز', '۲۴ عدد'],
      ['نوع نصب', 'کلیک روی ریل مگنتی'],
      ['سیستم هوشمند', 'Zigbee / KNX'],
      ['گارانتی', '۳ سال']
    ],
    camera: { pos: [3.2, 2.7, 4.8], target: [0, 1.75, 0], min: 2.6, max: 10, polarMax: 1.95 },
    presets: {
      three: [3.2, 2.7, 4.8],
      front: [0, 1.75, 6.0],
      side: [6.0, 1.75, 0],
      detail: [2.3, 2.2, 3.4]
    },
    build: buildMagnet
  },
  spot2r: {
    id: 'spot2r',
    letter: 'Σ',
    family: 'خانواده اسپات',
    name: 'چراغ اسپات توکار دوگانه ۲R',
    short: 'اسپات ۲R / دوگانه',
    desc: 'دو هد تنظیم‌پذیر در یک پلیت توکار؛ نور دقیق برای گالری و فضای تجاری.',
    image: 'assets/img/products/spot-2r-20.webp',
    url: 'https://pishgamnoor.com/products/spot-2r-20/',
    stage: 'indoor',
    finishes: INDOOR_FINISHES,
    ccts: [3000, 4000, 5000, 6000],
    specs: [
      ['توان', '۲ × ۲۰ وات'],
      ['شار نوری', '۲ × ۱۸۰۰ لومن'],
      ['CRI', '۸۰'],
      ['درجه حفاظت', 'IP44'],
      ['ابعاد', '۱۵۵ × ۸۰ × ۲۰۵ mm'],
      ['نوع نصب', 'توکار'],
      ['سیستم هوشمند', 'Zigbee / KNX'],
      ['گارانتی', '۲ سال']
    ],
    camera: { pos: [1.6, 1.5, 2.9], target: [0, 2.4, 0], min: 1.8, max: 8, polarMax: 1.9 },
    presets: {
      three: [1.6, 1.5, 2.9],
      front: [0, 1.5, 3.6],
      side: [3.6, 1.5, 0],
      detail: [1.1, 1.55, 2.2]
    },
    build: buildSpot2R
  },
  omegalinear: {
    id: 'omegalinear',
    letter: 'Ω',
    family: 'خانواده امگا',
    name: 'چراغ خطی امگا آویز',
    short: 'امگا / خطی آویز',
    desc: 'خط نوری باریک و آویز؛ نور یکنواخت روی میز و دیوار.',
    image: 'assets/img/products/omega-linear.webp',
    url: 'https://pishgamnoor.com/products/omega-linear-light/',
    stage: 'indoor',
    finishes: INDOOR_FINISHES,
    ccts: [3000, 4000, 5000, 6000],
    specs: [
      ['توان', '۱۰ تا ۲۰ وات / متر'],
      ['شار نوری', '۱۰۰۰ تا ۲۰۰۰ لومن'],
      ['CRI', 'بالای ۹۰'],
      ['درجه حفاظت', 'IP43'],
      ['مقطع پروفیل', '۱۶ mm'],
      ['نوع نصب', 'آویز / روکار'],
      ['سیستم هوشمند', 'Zigbee / KNX'],
      ['گارانتی', '۳ تا ۶ سال']
    ],
    camera: { pos: [4.8, 2.2, 3.2], target: [0, 1.9, 0], min: 3.0, max: 11, polarMax: 1.9 },
    presets: {
      three: [4.8, 2.2, 3.2],
      front: [0, 2.0, 5.8],
      side: [5.8, 2.0, 0],
      detail: [3.4, 2.0, 2.4]
    },
    build: buildOmegaLinear
  },
  inout: {
    id: 'inout',
    letter: 'Ι',
    family: 'خانواده اسپات',
    name: 'چراغ توکار تنظیم‌پذیر ایناوت',
    short: 'ایناوت / توکار',
    desc: 'اسپات توکار با هد متحرک؛ نور دقیق روی تابلو، ویترین و جزئیات.',
    image: 'assets/img/products/inout.webp',
    url: 'https://pishgamnoor.com/products/inout/',
    stage: 'indoor',
    finishes: INDOOR_FINISHES,
    ccts: [3000, 4000, 5000, 6000],
    specs: [
      ['توان', '۷ وات'],
      ['شار نوری', '۷۰۰ لومن'],
      ['CRI', 'بالای ۹۵'],
      ['درجه حفاظت', 'IP44'],
      ['قطر چراغ', '۵۰ mm'],
      ['نوع نصب', 'توکار / سوراخ ۴۵ mm'],
      ['سیستم هوشمند', 'Zigbee / KNX'],
      ['گارانتی', '۲ سال']
    ],
    camera: { pos: [1.0, 1.6, 2.3], target: [0, 2.4, 0], min: 1.4, max: 7, polarMax: 1.9 },
    presets: {
      three: [1.0, 1.6, 2.3],
      front: [0, 1.6, 2.8],
      side: [2.8, 1.6, 0],
      detail: [0.7, 1.7, 1.7]
    },
    build: buildInout
  },
  spotre: {
    id: 'spotre',
    letter: 'Ρ',
    family: 'خانواده اسپات',
    name: 'چراغ اسپات روکار سه‌گانه RE',
    short: 'اسپات RE / روکار',
    desc: 'سه هد روکار روی یک بار؛ نور جهت‌دار برای دیوار و فضای تجاری.',
    image: 'assets/img/products/spot-re.webp',
    url: 'https://pishgamnoor.com/products/spot-re/',
    stage: 'indoor',
    finishes: INDOOR_FINISHES,
    ccts: [3000, 4000, 5000, 6000],
    specs: [
      ['توان', '۳۰ وات'],
      ['شار نوری', '۲۷۰۰ لومن'],
      ['CRI', '۸۰'],
      ['درجه حفاظت', 'IP44'],
      ['ابعاد', '۱۶۰ × ۷۰ mm'],
      ['نوع نصب', 'روکار'],
      ['سیستم هوشمند', 'Zigbee / KNX'],
      ['گارانتی', '۲ سال']
    ],
    camera: {
      pos: [2.6, 1.8, 4.4], target: [0, 1.75, 0], min: 2.6, max: 9, polarMax: 1.75,
      azimuth: { min: -1.15, max: 1.15 }
    },
    presets: {
      three: [2.6, 1.8, 4.4],
      front: [0, 1.8, 5.0],
      side: [5.0, 1.8, 0],
      detail: [1.8, 1.7, 3.0]
    },
    build: buildSpotRE
  },
  beta2: {
    id: 'beta2',
    letter: 'Β',
    family: 'خانواده بتا',
    name: 'چراغ خطی دیواری بتا ۲',
    short: 'بتا ۲ / دیواری',
    desc: 'خط عمودی با نور غیرمستقیم؛ هاله‌ای از نور روی دیوار.',
    image: 'assets/img/products/beta-2.webp',
    url: 'https://pishgamnoor.com/products/beta-2-decorative/',
    stage: 'indoor',
    finishes: INDOOR_FINISHES,
    ccts: [3000, 4000, 5000, 6000],
    specs: [
      ['توان', '۱۰ تا ۲۰ وات / متر'],
      ['شار نوری', '۱۰۰۰ تا ۲۰۰۰ لومن'],
      ['CRI', 'بالای ۹۰'],
      ['درجه حفاظت', 'IP43'],
      ['مقطع پروفیل', '۱۷ × ۱۳ mm'],
      ['نوع نصب', 'دیواری'],
      ['سیستم هوشمند', 'Zigbee / KNX'],
      ['گارانتی', '۳ تا ۶ سال']
    ],
    camera: {
      pos: [2.2, 1.6, 4.0], target: [0, 1.45, 0], min: 2.0, max: 8, polarMax: 1.75,
      azimuth: { min: -1.05, max: 1.05 }
    },
    presets: {
      three: [2.2, 1.6, 4.0],
      front: [0, 1.6, 4.6],
      side: [4.6, 1.6, 0],
      detail: [1.5, 1.55, 2.8]
    },
    build: buildBeta2
  }
};

/* ---------------- WordPress data bridge ---------------- */

const PN_DATA = window.PishgamnoorStudioData || null;
const BASE_PRODUCTS = { ...PRODUCTS };

if (PN_DATA && Array.isArray(PN_DATA.products) && PN_DATA.products.length) {
  const next = {};

  PN_DATA.products.forEach((product) => {
    const definition = BASE_PRODUCTS[product.model] || BASE_PRODUCTS.gamma;
    if (!definition) return;

    next[product.id] = {
      ...definition,
      ...product,
      build: definition.build,
      camera: (product.camera && Array.isArray(product.camera.pos) && product.camera.pos.length === 3)
        ? product.camera
        : definition.camera,
      presets: (product.presets && product.presets.three) ? product.presets : definition.presets,
      finishes: (Array.isArray(product.finishes) && product.finishes.length) ? product.finishes : definition.finishes,
      ccts: (Array.isArray(product.ccts) && product.ccts.length) ? product.ccts : definition.ccts,
      specs: (Array.isArray(product.specs) && product.specs.length) ? product.specs : definition.specs,
      stage: product.stage || definition.stage,
    };
  });

  if (Object.keys(next).length) {
    Object.keys(PRODUCTS).forEach((key) => delete PRODUCTS[key]);
    Object.assign(PRODUCTS, next);
  }
}

/* ---------------- Builders ---------------- */

function buildGamma() {
  const g = makeGroup();
  const body = bodyMaterial('#16171c', 0.62, 0.38);
  const ceilingMat = new THREE.MeshStandardMaterial({
    color: '#b7b0a4',
    metalness: 0.02,
    roughness: 0.9,
    envMapIntensity: 0.25
  });

  const W = 3.9;
  const D = 2.9;
  const T = 0.15;
  const slotW = 0.3;
  const slotL = 2.7;
  const y = 2.7;
  const sideW = (W - slotW) / 2;
  const endD = (D - slotL) / 2;

  const left = rbox(sideW, T, D, 0.025, ceilingMat);
  left.position.set(-(slotW / 2 + sideW / 2), y, 0);
  const right = left.clone();
  right.position.x = slotW / 2 + sideW / 2;
  g.add(left, right);

  const front = rbox(slotW, T, endD, 0.02, ceilingMat);
  front.position.set(0, y, slotL / 2 + endD / 2);
  const back = front.clone();
  back.position.z = -(slotL / 2 + endD / 2);
  g.add(front, back);

  // Recessed Gamma: the body and diffuser sit *inside* the knauf slot,
  // flush with the ceiling underside — not proud of the ceiling.
  const frameTop = rbox(0.3, 0.06, 2.58, 0.012, body);
  frameTop.position.set(0, 2.7, 0);
  addBody(g, frameTop);
  g.add(frameTop);

  const railL = rbox(0.018, 0.04, 2.58, 0.008, body);
  railL.position.set(-0.141, 2.645, 0);
  const railR = railL.clone();
  railR.position.x = 0.141;
  addBody(g, railL);
  addBody(g, railR);
  g.add(railL, railR);

  const diffuserMat = ledMaterial('#ffb46b', 1.8);
  const diffuser = box(0.13, 0.012, 2.5, diffuserMat);
  diffuser.position.set(0, 2.631, 0);
  addLed(g, diffuser, 1.8);
  g.add(diffuser);

  // subtle glow around the recessed strip
  const stripGlow = glowSprite('#ffb46b', 0.15, 2.7, 0.5);
  stripGlow.position.set(0, 2.62, 0);
  addGlow(g, stripGlow, 0.15);
  g.add(stripGlow);

  // floor wash
  const wash = floorGlow(3.4, 2.4, '#ffb46b', 0.18);
  addGlow(g, wash, 0.18);
  g.add(wash);

  const positions = [-0.8, 0, 0.8];
  positions.forEach((z) => {
    const p = new THREE.PointLight('#ffb46b', 1.7, 4.8, 2);
    p.position.set(0, 2.22, z);
    addLight(g, p, 1.7);
    g.add(p);
  });

  // studio fill, stays on so the black profile never disappears
  const fill = new THREE.PointLight(0xfff2dd, 1.2, 5.5, 2);
  fill.position.set(0, 1.5, 2.6);
  g.add(fill);

  return g;
}

function buildTheta() {
  const g = makeGroup();
  const body = bodyMaterial('#0a0b0e', 0.15, 0.68);
  const ceilingMat = new THREE.MeshStandardMaterial({
    color: '#2f3339',
    metalness: 0.02,
    roughness: 0.94,
    envMapIntensity: 0.18
  });

  const start = Math.PI * 0.25;
  const end = Math.PI * 1.75;
  const inner = 0.96;
  const outer = 1.27;

  // Dark ceiling plane — the real product is a recessed C, not a pendant.
  const ceiling = rbox(5.0, 0.08, 3.8, 0.02, ceilingMat);
  ceiling.position.set(0, 2.84, 0);
  g.add(ceiling);

  const addC = (scale, x, z, y = 2.76, rotY = 0) => {
    const c = new THREE.Group();
    c.position.set(x, y, z);
    c.scale.setScalar(scale);
    c.rotation.y = rotY;

    // black C body, laid flat against the ceiling
    const shape = crescentShape(inner, outer, start, end);
    const geo = new THREE.ExtrudeGeometry(shape, {
      depth: 0.12,
      bevelEnabled: true,
      bevelSegments: 3,
      bevelSize: 0.01,
      bevelThickness: 0.01,
      curveSegments: 72
    });
    geo.translate(0, 0, -0.06);
    geo.rotateX(-Math.PI / 2);
    const bodyMesh = new THREE.Mesh(geo, body);
    addBody(g, bodyMesh);
    c.add(bodyMesh);

    // white diffuser band on the underside, close to the inner edge
    const bandGeo = new THREE.ExtrudeGeometry(
      crescentShape(1.0, 1.17, start, end),
      { depth: 0.006, bevelEnabled: false, curveSegments: 72 }
    );
    bandGeo.translate(0, 0, -0.003);
    bandGeo.rotateX(-Math.PI / 2);
    const band = new THREE.Mesh(bandGeo, ledMaterial('#ffb46b', 1.7));
    band.position.y = -0.067;
    addLed(g, band, 1.7);
    c.add(band);

    // glowing inner lip
    const torusGeo = new THREE.TorusGeometry(inner + 0.018, 0.028, 10, 160, end - start);
    torusGeo.rotateZ(start);
    torusGeo.rotateX(-Math.PI / 2);
    const torus = new THREE.Mesh(torusGeo, ledMaterial('#ffb46b', 2.0));
    torus.position.y = -0.066;
    addLed(g, torus, 2.0);
    c.add(torus);

    // vertical inner diffuser wall: this is what reads as a broad glow from below
    const wallMat = ledMaterial('#ffb46b', 1.8);
    wallMat.side = THREE.DoubleSide;
    const innerWall = new THREE.Mesh(
      new THREE.CylinderGeometry(inner + 0.022, inner + 0.022, 0.09, 80, 1, true, start + Math.PI / 2, end - start),
      wallMat
    );
    innerWall.position.y = -0.028;
    addLed(g, innerWall, 1.8);
    c.add(innerWall);

    // soft light under the C
    const p = new THREE.PointLight('#ffb46b', 2.4, 4.2, 2);
    p.position.set(0, -0.3, 0);
    addLight(g, p, 2.4);
    c.add(p);

    g.add(c);
    return c;
  };

  // one large C plus two smaller ones, like the reference hallway
  addC(0.92, 0, 0, 2.8, 0.1);
  addC(0.46, 1.15, 0.7, 2.8, -0.35);
  addC(0.34, -1.0, 0.95, 2.8, 0.45);

  const wash = floorGlow(3.6, 2.8, '#ffb46b', 0.1);
  wash.position.set(0, 0.013, 0.25);
  addGlow(g, wash, 0.1);
  g.add(wash);

  // neutral studio fill + cool rim, kept on so the black C never disappears
  const fill = new THREE.PointLight(0xfff1dc, 2.6, 9, 2);
  fill.position.set(2.2, 1.5, 3.8);
  g.add(fill);
  const fill2 = new THREE.PointLight(0xffffff, 1.2, 7, 2);
  fill2.position.set(-2.2, 1.4, 2.6);
  g.add(fill2);
  const rim = new THREE.PointLight(0xdfe9ff, 1.2, 6, 2);
  rim.position.set(-2.6, 2.1, -2.2);
  g.add(rim);

  return g;
}

function buildOmega() {
  const g = makeGroup();
  const body = bodyMaterial('#1b1c22', 0.34, 0.56);

  const bar = rbox(0.09, 2.35, 0.09, 0.012, body);
  bar.position.set(0, 1.2, 0);
  addBody(g, bar);
  g.add(bar);

  const topCap = rbox(0.1, 0.028, 0.1, 0.006, body);
  topCap.position.set(0, 2.39, 0);
  addBody(g, topCap);
  g.add(topCap);

  const baseX = rbox(0.86, 0.045, 0.09, 0.01, body);
  baseX.position.set(0.39, 0.032, 0);
  const baseZ = rbox(0.09, 0.045, 0.86, 0.01, body);
  baseZ.position.set(0, 0.032, 0.39);
  addBody(g, baseX);
  addBody(g, baseZ);
  g.add(baseX, baseZ);

  const footCap = rbox(0.11, 0.05, 0.11, 0.01, body);
  footCap.position.set(0, 0.034, 0);
  addBody(g, footCap);
  g.add(footCap);

  // The real product emits from the outer left edge and washes the wall behind it.
  const stripMat = ledMaterial('#ffb46b', 2.6);
  const strip = box(0.009, 2.22, 0.022, stripMat);
  strip.position.set(-0.051, 1.22, 0);
  addLed(g, strip, 2.6);
  g.add(strip);

  // tall, soft wall wash (a sprite keeps the 3D stage open for 360° orbit)
  const wallGlow = glowSprite('#ffb46b', 0.3, 2.8, 3.6);
  wallGlow.position.set(-0.55, 1.45, -0.22);
  addGlow(g, wallGlow, 0.3);
  g.add(wallGlow);

  const halo = glowSprite('#ffb46b', 0.18, 1.1, 3.0);
  halo.position.set(-0.18, 1.35, 0);
  addGlow(g, halo, 0.18);
  g.add(halo);

  const wash = floorGlow(2.4, 1.4, '#ffb46b', 0.18);
  wash.position.set(-0.2, 0.013, 0.1);
  addGlow(g, wash, 0.18);
  g.add(wash);

  const shadow = contactShadow(1.6, 1.4, 0.58);
  shadow.position.set(0.18, 0.008, 0.16);
  g.add(shadow);

  [0.45, 1.2, 1.95].forEach((y) => {
    const p = new THREE.PointLight('#ffb46b', 2.2, 3.4, 2);
    p.position.set(-0.16, y, 0.02);
    addLight(g, p, 2.2);
    g.add(p);
  });

  return g;
}

function buildZeous() {
  const g = makeGroup();
  const body = bodyMaterial('#1b1c22', 0.3, 0.58);
  const dark = darkStageMaterial('#1a1f27', 0.86);

  const platform = cylinder(1.35, 1.35, 0.035, 64, dark);
  platform.position.y = 0.018;
  platform.receiveShadow = true;
  g.add(platform);

  const base = rbox(0.3, 0.045, 0.3, 0.006, body);
  base.position.y = 0.045;
  addBody(g, base);
  g.add(base);

  const pole = rbox(0.09, 2.55, 0.09, 0.008, body);
  pole.position.y = 1.3;
  addBody(g, pole);
  g.add(pole);

  const collar = rbox(0.105, 0.05, 0.105, 0.006, body);
  collar.position.y = 2.54;
  addBody(g, collar);
  g.add(collar);

  const upper = new THREE.Group();
  upper.position.set(0, 2.55, 0);
  upper.rotation.z = 0.33;
  const upperArm = rbox(0.86, 0.075, 0.09, 0.01, body);
  upperArm.position.set(0.43, 0, 0);
  addBody(g, upperArm);
  upper.add(upperArm);

  const upperEnd = rbox(0.07, 0.08, 0.095, 0.01, body);
  upperEnd.position.set(0.84, 0, 0);
  addBody(g, upperEnd);
  upper.add(upperEnd);

  const lensMat = ledMaterial('#ffb46b', 2.8);
  for (let i = 0; i < 7; i += 1) {
    const x = 0.1 + i * 0.105;
    const puck = cylinder(0.021, 0.021, 0.012, 12, lensMat);
    puck.position.set(x, -0.045, 0);
    addLed(g, puck, 2.8);
    upper.add(puck);
  }
  g.add(upper);

  const lower = new THREE.Group();
  lower.position.set(0, 1.55, 0);
  lower.rotation.z = -0.16;
  const lowerArm = rbox(0.6, 0.06, 0.08, 0.01, body);
  lowerArm.position.set(-0.3, 0, 0);
  addBody(g, lowerArm);
  lower.add(lowerArm);

  const lowerEnd = rbox(0.07, 0.065, 0.085, 0.008, body);
  lowerEnd.position.set(-0.6, 0, 0);
  addBody(g, lowerEnd);
  lower.add(lowerEnd);

  for (let i = 0; i < 3; i += 1) {
    const x = -0.12 - i * 0.14;
    const puck = cylinder(0.018, 0.018, 0.01, 12, lensMat);
    puck.position.set(x, -0.038, 0);
    addLed(g, puck, 2.4);
    lower.add(puck);
  }
  g.add(lower);

  const poolA = floorGlow(2.5, 1.4, '#ffb46b', 0.3);
  poolA.position.set(1.15, 0.014, 0.15);
  addGlow(g, poolA, 0.3);
  g.add(poolA);

  const poolB = floorGlow(1.8, 1.0, '#ffb46b', 0.22);
  poolB.position.set(-0.75, 0.014, 0.15);
  addGlow(g, poolB, 0.22);
  g.add(poolB);

  const poolC = floorGlow(1.5, 1.5, '#ffb46b', 0.12);
  poolC.position.set(0, 0.014, 0);
  addGlow(g, poolC, 0.12);
  g.add(poolC);

  const shadow = contactShadow(2.4, 2.4, 0.52);
  g.add(shadow);

  const upperSpot = new THREE.SpotLight('#ffb46b', 14, 9, 0.55, 0.85, 1.6);
  upperSpot.position.set(0.72, 2.78, 0);
  upperSpot.target.position.set(1.9, 0, 0);
  upperSpot.castShadow = !isMobile;
  if (upperSpot.castShadow) {
    upperSpot.shadow.mapSize.set(1024, 1024);
    upperSpot.shadow.bias = -0.0004;
  }
  addLight(g, upperSpot, 14);
  g.add(upperSpot, upperSpot.target);

  const lowerSpot = new THREE.SpotLight('#ffb46b', 9, 7, 0.58, 0.9, 1.7);
  lowerSpot.position.set(-0.5, 1.56, 0);
  lowerSpot.target.position.set(-1.5, 0, 0);
  addLight(g, lowerSpot, 9);
  g.add(lowerSpot, lowerSpot.target);

  const topPoint = new THREE.PointLight('#ffb46b', 3.0, 4.5, 2);
  topPoint.position.set(0.35, 2.75, 0.15);
  addLight(g, topPoint, 3.0);
  g.add(topPoint);

  return g;
}

function buildMagnet() {
  const g = makeGroup();
  const body = bodyMaterial('#1b1c22', 0.34, 0.56);

  // The reference image is a single magnetic linear module, not a full track installation.
  const module = rbox(0.14, 0.115, 2.48, 0.012, body);
  module.position.set(0, 2.35, 0);
  addBody(g, module);
  g.add(module);

  const clip = rbox(0.09, 0.05, 0.2, 0.008, body);
  clip.position.set(0, 2.44, 0);
  addBody(g, clip);
  g.add(clip);

  // short piece of magnetic rail above the clip, for context
  const track = rbox(0.11, 0.045, 0.62, 0.008, body);
  track.position.set(0, 2.49, 0);
  addBody(g, track);
  g.add(track);

  const lensMat = ledMaterial('#ffb46b', 2.6);
  const ringMat = bodyMaterial('#202127', 0.4, 0.55);
  const count = 24;
  for (let i = 0; i < count; i += 1) {
    const z = -1.12 + i * (2.24 / (count - 1));
    const ring = cylinder(0.031, 0.031, 0.006, 16, ringMat);
    ring.position.set(0, 2.291, z);
    const puck = cylinder(0.023, 0.023, 0.011, 14, lensMat);
    puck.position.set(0, 2.286, z);
    addLed(g, puck, 2.6);
    g.add(ring, puck);
  }

  const stripGlow = glowSprite('#ffb46b', 0.05, 0.7, 2.6);
  stripGlow.position.set(0, 2.3, 0);
  addGlow(g, stripGlow, 0.05);
  g.add(stripGlow);

  const wash = floorGlow(3.0, 1.8, '#ffb46b', 0.16);
  addGlow(g, wash, 0.16);
  g.add(wash);

  const shadow = contactShadow(2.8, 1.4, 0.44);
  g.add(shadow);

  [-0.95, -0.32, 0.32, 0.95].forEach((z) => {
    const p = new THREE.PointLight('#ffb46b', 2.0, 3.0, 2);
    p.position.set(0, 2.25, z);
    addLight(g, p, 2.0);
    g.add(p);
  });

  const fill = new THREE.PointLight(0xfff1dc, 2.2, 6, 2);
  fill.position.set(1.8, 2.55, 2.8);
  g.add(fill);

  return g;
}

/* ---------------- Additional product builders ---------------- */

function buildSpot2R() {
  const g = makeGroup();
  const body = bodyMaterial('#1b1c22', 0.34, 0.56);
  const ceilingMat = new THREE.MeshStandardMaterial({
    color: '#b7b0a4',
    metalness: 0.02,
    roughness: 0.9,
    envMapIntensity: 0.25
  });

  const ceiling = rbox(3.6, 0.08, 2.8, 0.02, ceilingMat);
  ceiling.position.set(0, 2.8, 0);
  g.add(ceiling);

  const plate = rbox(0.34, 0.035, 0.44, 0.008, body);
  plate.position.set(0, 2.75, 0);
  addBody(g, plate);
  g.add(plate);

  const reflectorMat = new THREE.MeshStandardMaterial({
    color: '#d8d4c8',
    metalness: 0.65,
    roughness: 0.24,
    emissive: new THREE.Color('#ffb46b'),
    emissiveIntensity: 0.28
  });

  const makeHead = (x, z, rz, rx) => {
    const head = new THREE.Group();
    head.position.set(x, 2.55, z);
    head.rotation.z = rz;
    head.rotation.x = rx;

    const stem = cylinder(0.012, 0.012, 0.16, 12, body);
    stem.position.y = 0.15;
    addBody(g, stem);
    head.add(stem);

    const barrel = cylinder(0.075, 0.075, 0.25, 24, body);
    barrel.position.y = -0.05;
    addBody(g, barrel);
    head.add(barrel);

    const rim = cylinder(0.079, 0.079, 0.025, 24, body);
    rim.position.y = -0.18;
    addBody(g, rim);
    head.add(rim);

    const reflector = cylinder(0.062, 0.062, 0.018, 24, reflectorMat);
    reflector.position.y = -0.196;
    head.add(reflector);

    const lens = cylinder(0.046, 0.046, 0.012, 24, ledMaterial('#ffb46b', 2.2));
    lens.position.y = -0.207;
    addLed(g, lens, 2.2);
    head.add(lens);

    const spot = new THREE.SpotLight('#ffb46b', 7.5, 5.5, 0.5, 0.86, 1.6);
    spot.position.set(0, -0.24, 0);
    spot.target.position.set(x * 1.6, -2.2, 1.0);
    addLight(g, spot, 7.5);
    head.add(spot, spot.target);

    g.add(head);
  };

  makeHead(-0.1, 0.03, 0.22, 0.3);
  makeHead(0.1, -0.03, -0.22, 0.36);

  const washA = floorGlow(1.4, 1.4, '#ffb46b', 0.18);
  washA.position.set(-0.4, 0.013, 0.5);
  addGlow(g, washA, 0.18);
  g.add(washA);

  const washB = floorGlow(1.4, 1.4, '#ffb46b', 0.18);
  washB.position.set(0.4, 0.013, 0.5);
  addGlow(g, washB, 0.18);
  g.add(washB);

  const shadow = contactShadow(1.6, 1.6, 0.35);
  g.add(shadow);

  const fill = new THREE.PointLight(0xfff1dc, 1.8, 5.5, 2);
  fill.position.set(1.5, 1.2, 2.5);
  g.add(fill);

  return g;
}

function buildOmegaLinear() {
  const g = makeGroup();
  const body = bodyMaterial('#1b1c22', 0.34, 0.56);
  const cableMat = bodyMaterial('#1b1c22', 0.3, 0.6);

  const canopy = rbox(0.5, 0.022, 0.1, 0.005, body);
  canopy.position.set(0, 3.32, 0);
  addBody(g, canopy);
  g.add(canopy);

  const bar = rbox(0.06, 0.06, 2.6, 0.008, body);
  bar.position.set(0, 2.3, 0);
  addBody(g, bar);
  g.add(bar);

  const diffuser = box(0.044, 0.008, 2.54, ledMaterial('#ffb46b', 2.2));
  diffuser.position.set(0, 2.267, 0);
  addLed(g, diffuser, 2.2);
  g.add(diffuser);

  [-0.85, 0.85].forEach((z) => {
    const cable = makeCable(
      new THREE.Vector3(0, 2.33, z),
      new THREE.Vector3(0, 3.31, z),
      0.004,
      cableMat
    );
    addBody(g, cable);
    g.add(cable);
  });

  const stripGlow = glowSprite('#ffb46b', 0.12, 0.65, 2.7);
  stripGlow.position.set(0, 2.26, 0);
  addGlow(g, stripGlow, 0.12);
  g.add(stripGlow);

  const wash = floorGlow(3.0, 1.7, '#ffb46b', 0.14);
  addGlow(g, wash, 0.14);
  g.add(wash);

  const shadow = contactShadow(2.6, 1.2, 0.28);
  g.add(shadow);

  [-0.8, 0, 0.8].forEach((z) => {
    const p = new THREE.PointLight('#ffb46b', 1.9, 4.0, 2);
    p.position.set(0, 2.16, z);
    addLight(g, p, 1.9);
    g.add(p);
  });

  const fill = new THREE.PointLight(0xfff1dc, 1.6, 7, 2);
  fill.position.set(1.6, 2.2, 3.0);
  g.add(fill);

  return g;
}

function buildInout() {
  const g = makeGroup();
  const white = bodyMaterial('#e9e5db', 0.14, 0.62);
  const black = bodyMaterial('#111216', 0.28, 0.56);
  const ceilingMat = new THREE.MeshStandardMaterial({
    color: '#ddd8ce',
    metalness: 0.02,
    roughness: 0.92,
    envMapIntensity: 0.22
  });

  const ceiling = rbox(3.2, 0.08, 2.6, 0.02, ceilingMat);
  ceiling.position.set(0, 2.8, 0);
  g.add(ceiling);

  const trim = cylinder(0.15, 0.16, 0.03, 40, white);
  trim.position.set(0, 2.765, 0);
  addBody(g, trim);
  g.add(trim);

  const hole = cylinder(0.108, 0.108, 0.018, 32, black);
  hole.position.set(0, 2.782, 0);
  g.add(hole);

  const housing = cylinder(0.075, 0.075, 0.2, 24, black);
  housing.position.set(0, 2.88, 0);
  g.add(housing);

  const head = new THREE.Group();
  head.position.set(0, 2.67, 0);
  head.rotation.x = 0.55;
  head.rotation.z = 0.18;

  const neck = cylinder(0.06, 0.06, 0.17, 24, white);
  neck.position.y = -0.02;
  addBody(g, neck);
  head.add(neck);

  const ring = cylinder(0.063, 0.063, 0.024, 24, black);
  ring.position.y = -0.108;
  head.add(ring);

  const lens = cylinder(0.044, 0.044, 0.012, 24, ledMaterial('#ffb46b', 3.2));
  lens.position.y = -0.121;
  addLed(g, lens, 3.2);
  head.add(lens);

  g.add(head);

  const pool = floorGlow(1.3, 1.3, '#ffb46b', 0.22);
  pool.position.set(0.35, 0.013, 0.85);
  addGlow(g, pool, 0.22);
  g.add(pool);

  const spot = new THREE.SpotLight('#ffb46b', 9, 6, 0.42, 0.9, 1.5);
  spot.position.set(0.18, 2.45, 0.26);
  spot.target.position.set(0.45, 0, 1.1);
  addLight(g, spot, 9);
  g.add(spot, spot.target);

  const fill = new THREE.PointLight(0xfff1dc, 1.5, 6, 2);
  fill.position.set(1.4, 1.7, 2.6);
  g.add(fill);

  return g;
}

function buildSpotRE() {
  const g = makeGroup();
  const body = bodyMaterial('#1b1c22', 0.34, 0.56);
  const ceilingMat = new THREE.MeshStandardMaterial({
    color: '#d8d3ca',
    metalness: 0.02,
    roughness: 0.92,
    envMapIntensity: 0.2
  });
  const woodMat = new THREE.MeshStandardMaterial({
    map: makeWoodTexture(),
    metalness: 0.02,
    roughness: 0.82,
    envMapIntensity: 0.2
  });

  const wall = new THREE.Mesh(new THREE.PlaneGeometry(5.0, 3.0), woodMat);
  wall.position.set(0, 1.5, -1.2);
  wall.receiveShadow = true;
  g.add(wall);

  const ceiling = rbox(5.0, 0.08, 3.0, 0.02, ceilingMat);
  ceiling.position.set(0, 2.8, -0.2);
  g.add(ceiling);

  const canopy = cylinder(0.1, 0.1, 0.045, 24, body);
  canopy.position.set(0, 2.57, 0);
  addBody(g, canopy);
  g.add(canopy);

  const bar = rbox(0.82, 0.06, 0.09, 0.01, body);
  bar.position.set(0, 2.5, 0);
  addBody(g, bar);
  g.add(bar);

  const reflectorMat = new THREE.MeshStandardMaterial({
    color: '#d8d4c8',
    metalness: 0.62,
    roughness: 0.26,
    emissive: new THREE.Color('#ffb46b'),
    emissiveIntensity: 0.25
  });

  [-0.28, 0, 0.28].forEach((x, i) => {
    const head = new THREE.Group();
    head.position.set(x, 2.4, 0);
    head.rotation.z = i === 0 ? -0.14 : i === 2 ? 0.14 : 0;
    head.rotation.x = 0.16;

    const stem = cylinder(0.01, 0.01, 0.13, 12, body);
    stem.position.y = 0.11;
    addBody(g, stem);
    head.add(stem);

    const barrel = rbox(0.095, 0.2, 0.095, 0.012, body);
    barrel.position.y = -0.02;
    addBody(g, barrel);
    head.add(barrel);

    const rim = cylinder(0.054, 0.054, 0.02, 20, body);
    rim.position.y = -0.125;
    addBody(g, rim);
    head.add(rim);

    const reflector = cylinder(0.046, 0.046, 0.015, 20, reflectorMat);
    reflector.position.y = -0.137;
    head.add(reflector);

    const lens = cylinder(0.034, 0.034, 0.01, 20, ledMaterial('#ffb46b', 2.9));
    lens.position.y = -0.146;
    addLed(g, lens, 2.9);
    head.add(lens);

    const spot = new THREE.SpotLight('#ffb46b', 9, 5.5, 0.3, 0.7, 1.5);
    spot.position.set(x, 2.33, 0.02);
    spot.target.position.set(x * 2.0, 1.42, -1.15);
    addLight(g, spot, 9);
    g.add(spot, spot.target);

    g.add(head);
  });

  const wash = floorGlow(2.8, 1.6, '#ffb46b', 0.12);
  wash.position.set(0, 0.013, 0.55);
  addGlow(g, wash, 0.12);
  g.add(wash);

  const fill = new THREE.PointLight(0xfff1dc, 1.8, 7, 2);
  fill.position.set(2.2, 1.8, 2.8);
  g.add(fill);

  return g;
}

function buildBeta2() {
  const g = makeGroup();
  const body = bodyMaterial('#111216', 0.2, 0.62);
  const wallMat = new THREE.MeshStandardMaterial({
    color: '#ded9cf',
    metalness: 0.01,
    roughness: 0.96,
    envMapIntensity: 0.16
  });
  const stairMat = new THREE.MeshStandardMaterial({
    color: '#ab8d6e',
    metalness: 0.02,
    roughness: 0.82,
    envMapIntensity: 0.18
  });

  const wall = new THREE.Mesh(new THREE.PlaneGeometry(3.8, 3.0), wallMat);
  wall.position.set(0, 1.5, -0.5);
  wall.receiveShadow = true;
  g.add(wall);

  // minimal staircase, matching the reference photo's context
  for (let i = 0; i < 4; i += 1) {
    const step = rbox(0.62, 0.28, 0.68, 0.012, stairMat);
    step.position.set(1.18, 0.14 + i * 0.28, -0.02);
    step.receiveShadow = true;
    g.add(step);
  }

  const bar = rbox(0.036, 2.18, 0.036, 0.008, body);
  bar.position.set(0, 1.55, 0);
  addBody(g, bar);
  g.add(bar);

  const cross = rbox(0.5, 0.036, 0.036, 0.008, body);
  cross.position.set(0, 1.05, 0);
  addBody(g, cross);
  g.add(cross);

  const halo = glowSprite('#ffb46b', 0.22, 0.4, 2.45);
  halo.position.set(0, 1.55, -0.22);
  addGlow(g, halo, 0.22);
  g.add(halo);

  const crossGlow = glowSprite('#ffb46b', 0.08, 0.62, 0.18);
  crossGlow.position.set(0, 1.05, -0.22);
  addGlow(g, crossGlow, 0.08);
  g.add(crossGlow);

  const backLight = new THREE.PointLight('#ffb46b', 0.8, 1.2, 2);
  backLight.position.set(0, 1.55, -0.28);
  addLight(g, backLight, 0.8);
  g.add(backLight);

  const crossLight = new THREE.PointLight('#ffb46b', 0.3, 0.8, 2);
  crossLight.position.set(0, 1.05, -0.26);
  addLight(g, crossLight, 0.3);
  g.add(crossLight);

  const wash = floorGlow(1.4, 0.8, '#ffb46b', 0.08);
  wash.position.set(0, 0.013, 0.2);
  addGlow(g, wash, 0.08);
  g.add(wash);

  const fill = new THREE.PointLight(0xfff1dc, 1.2, 7, 2);
  fill.position.set(2.0, 1.7, 2.8);
  g.add(fill);

  return g;
}

/* ---------------- Scene state ---------------- */

const initialProduct = (PN_DATA?.defaultProduct && PRODUCTS[PN_DATA.defaultProduct])
  ? PN_DATA.defaultProduct
  : (Object.keys(PRODUCTS)[0] || 'gamma');

const state = {
  product: initialProduct,
  finish: 'black',
  cct: 3000,
  dim: 1,
  power: true,
  powerLevel: 1,
  autoRotate: PN_DATA?.settings?.autoRotate ?? !reduceMotion
};

let stage;
let canvas;
let loader;
let fallbackImg;
let threeReady = false;
let isVisible = false;
let renderer;
let scene;
let camera;
let controls;
let composer;
let bloomPass;
let floor;
let floorMat;
let skyMat;
let ambient;
let keyLight;
let fillLight;
let rimLight;
let currentGroup = null;
let cameraTween = null;
let autoRotateBeforeTween = false;
let resizeObserver;
let stageObserver;
let clock;

const stageTargets = {
  skyTop: new THREE.Color('#0b0e1c'),
  skyBottom: new THREE.Color('#05060e'),
  floor: new THREE.Color('#0b0d13'),
  fog: new THREE.Color('#080a14')
};

/* ---------------- UI ---------------- */

function getProduct() {
  return PRODUCTS[state.product] || PRODUCTS.gamma || PRODUCTS[Object.keys(PRODUCTS)[0]] || null;
}

function cameraPosFor(product, pos) {
  const factor = window.innerWidth <= 1024 ? 0.86 : 1;
  if (factor === 1) return pos;
  return pos.map((v, i) => product.camera.target[i] + (v - product.camera.target[i]) * factor);
}

function getFinish() {
  const p = getProduct();
  return p.finishes.find((f) => f.id === state.finish) || p.finishes[0];
}

function renderRail() {
  const rail = $('#studioRail');
  if (!rail) return;
  rail.innerHTML = Object.values(PRODUCTS).map((p) => `
    <button class="rail-item" data-product="${p.id}" aria-pressed="${p.id === state.product}" aria-label="${p.name}">
      <span class="rail-thumb"><img src="${p.image}" alt="" loading="lazy"></span>
      <span class="rail-text"><strong>${p.short}</strong><span>${p.family}</span></span>
    </button>
  `).join('');

  $$('.rail-item', rail).forEach((btn) => {
    btn.addEventListener('click', () => selectProduct(btn.dataset.product));
  });
}

function renderFinishes() {
  const wrap = $('#finishSwatches');
  if (!wrap) return;
  const p = getProduct();
  wrap.innerHTML = p.finishes.map((f) => `
    <button type="button" data-finish="${f.id}" class="${f.id === state.finish ? 'is-active' : ''}" style="--sw:${f.color}" aria-label="${f.label}" aria-pressed="${f.id === state.finish}"></button>
  `).join('');
  $$('button', wrap).forEach((btn) => {
    btn.addEventListener('click', () => {
      state.finish = btn.dataset.finish;
      renderFinishes();
      applyFinish();
    });
  });
  const label = $('#finishLabel');
  if (label) label.textContent = getFinish().label;
}

function renderCCT() {
  const wrap = $('#cctChips');
  if (!wrap) return;
  const p = getProduct();
  wrap.innerHTML = p.ccts.map((k) => `
    <button type="button" data-cct="${k}" class="${k === state.cct ? 'is-active' : ''}" aria-pressed="${k === state.cct}">
      <i style="--c:${CCT_HEX[k]}"></i>${faPlain(k)}K
    </button>
  `).join('');
  $$('button', wrap).forEach((btn) => {
    btn.addEventListener('click', () => {
      state.cct = Number(btn.dataset.cct);
      renderCCT();
      applyCCT();
    });
  });
  const label = $('#cctLabel');
  if (label) label.textContent = `${faPlain(state.cct)}K`;
  updateCCTReadout();
}

function renderSpecs() {
  const grid = $('#specGrid');
  if (!grid) return;
  const p = getProduct();
  grid.innerHTML = p.specs.map(([label, value]) => `
    <div class="spec-item"><span>${label}</span><b>${value}</b></div>
  `).join('');
}

function renderProductUI() {
  const p = getProduct();
  if (!p) return;

  const family = $('#studioFamily');
  const title = $('#studioTitle');
  const desc = $('#studioDesc');
  const productLink = $('#studioProductLink');
  if (family) family.textContent = p.family || '';
  if (title) title.textContent = p.name || '';
  if (desc) desc.textContent = p.desc || '';
  if (fallbackImg && p.image) fallbackImg.src = p.image;

  if (productLink) {
    if (p.url) {
      productLink.href = p.url;
      productLink.style.display = '';
    } else {
      productLink.style.display = 'none';
    }
  }
  renderFinishes();
  renderCCT();
  renderSpecs();
  $$('.rail-item').forEach((btn) => {
    const active = btn.dataset.product === state.product;
    btn.classList.toggle('is-active', active);
    btn.setAttribute('aria-pressed', String(active));
  });
}

function updateCCTReadout() {
  const el = $('#studioCctReadout');
  if (!el) return;
  const b = $('b', el);
  const dot = $('.cct-dot', el);
  if (b) b.textContent = `${faPlain(state.cct)}K`;
  if (dot) {
    dot.style.background = CCT_HEX[state.cct] || '#ffb46b';
    dot.style.boxShadow = `0 0 14px 3px ${CCT_HEX[state.cct] || '#ffb46b'}88`;
  }
}

function updatePowerUI() {
  const btn = $('#studioPower');
  const label = $('#powerLabel');
  if (btn) {
    btn.classList.toggle('is-on', state.power);
    btn.setAttribute('aria-pressed', String(state.power));
  }
  if (label) label.textContent = state.power ? 'روشن' : 'خاموش';
}

function updateDimUI() {
  const label = $('#dimLabel');
  if (label) label.textContent = `${faNum(Math.round(state.dim * 100))}٪`;
}

/* ---------------- URL / sharing ---------------- */

function updateURL() {
  try {
    const url = new URL(window.location.href);
    if (state.product === 'gamma') url.searchParams.delete('p');
    else url.searchParams.set('p', state.product);
    url.searchParams.set('finish', state.finish);
    url.searchParams.set('cct', String(state.cct));
    window.history.replaceState({}, '', url);
  } catch (_) {
    /* file protocol or restricted history */
  }
}

/* ---------------- Three.js setup ---------------- */

function setStageTargets(stageName) {
  if (stageName === 'outdoor') {
    stageTargets.skyTop.set('#07142b');
    stageTargets.skyBottom.set('#1b2734');
    stageTargets.floor.set('#151b22');
    stageTargets.fog.set('#101826');
  } else {
    stageTargets.skyTop.set('#171d31');
    stageTargets.skyBottom.set('#0a0d18');
    stageTargets.floor.set('#11151f');
    stageTargets.fog.set('#0d111c');
  }
}

function makeSky() {
  const geo = new THREE.SphereGeometry(30, 32, 32);
  skyMat = new THREE.ShaderMaterial({
    side: THREE.BackSide,
    depthWrite: false,
    uniforms: {
      topColor: { value: new THREE.Color('#0b0e1c') },
      bottomColor: { value: new THREE.Color('#05060e') }
    },
    vertexShader: `
      varying vec3 vWorldPosition;
      void main() {
        vec4 worldPosition = modelMatrix * vec4(position, 1.0);
        vWorldPosition = worldPosition.xyz;
        gl_Position = projectionMatrix * modelViewMatrix * vec4(position, 1.0);
      }
    `,
    fragmentShader: `
      uniform vec3 topColor;
      uniform vec3 bottomColor;
      varying vec3 vWorldPosition;
      void main() {
        float h = normalize(vWorldPosition).y;
        float t = smoothstep(-0.18, 0.7, h);
        gl_FragColor = vec4(mix(bottomColor, topColor, t), 1.0);
      }
    `
  });
  const sky = new THREE.Mesh(geo, skyMat);
  scene.add(sky);
}

function initThree() {
  if (threeReady) return;
  stage = $('#studioStage');
  canvas = $('#studioCanvas');
  loader = $('#studioLoader');
  fallbackImg = $('#studioFallbackImg');
  if (!stage || !canvas) return;

  // WebGL availability
  try {
    const test = document.createElement('canvas');
    const gl = test.getContext('webgl2') || test.getContext('webgl');
    if (!gl) throw new Error('WebGL unavailable');
  } catch (_) {
    stage.closest('.studio-shell')?.classList.add('no-webgl');
    loader?.classList.add('is-hidden');
    return;
  }

  try {
    renderer = new THREE.WebGLRenderer({
      canvas,
      antialias: true,
      alpha: false,
      powerPreference: 'high-performance',
      preserveDrawingBuffer: true
    });
    renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, isMobile ? 1.5 : 2));
    renderer.outputColorSpace = THREE.SRGBColorSpace;
    renderer.toneMapping = THREE.ACESFilmicToneMapping;
    renderer.toneMappingExposure = 1.02;
    renderer.shadowMap.enabled = !isMobile;
    renderer.shadowMap.type = THREE.PCFSoftShadowMap;

    scene = new THREE.Scene();
    scene.fog = new THREE.FogExp2(0x080a14, 0.028);

    const rect = stage.getBoundingClientRect();
    const w = Math.max(rect.width, 320);
    const h = Math.max(rect.height, 320);

    camera = new THREE.PerspectiveCamera(36, w / h, 0.1, 100);
    camera.position.set(4.5, 2.2, 5.9);

    controls = new OrbitControls(camera, canvas);
    controls.enableDamping = true;
    controls.dampingFactor = 0.075;
    controls.enablePan = false;
    controls.minDistance = 3.6;
    controls.maxDistance = 11;
    controls.minPolarAngle = 0.16;
    controls.maxPolarAngle = Math.PI / 2 - 0.02;
    controls.autoRotate = state.autoRotate && !reduceMotion;
    controls.autoRotateSpeed = 0.55;
    controls.target.set(0, 1.9, 0);

    controls.addEventListener('start', () => {
      if (state.autoRotate) {
        state.autoRotate = false;
        controls.autoRotate = false;
        const btn = $('#studioRotate');
        btn?.setAttribute('aria-pressed', 'false');
        btn?.classList.remove('is-active');
      }
    });

    // Environment
    const pmrem = new THREE.PMREMGenerator(renderer);
    const env = pmrem.fromScene(new RoomEnvironment(), 0.04).texture;
    scene.environment = env;
    if ('environmentIntensity' in scene) scene.environmentIntensity = 0.32;

    makeSky();

    floorMat = new THREE.MeshStandardMaterial({
      color: '#0b0d13',
      metalness: 0.28,
      roughness: 0.52,
      envMapIntensity: 0.35
    });
    floor = new THREE.Mesh(new THREE.CircleGeometry(18, 80), floorMat);
    floor.rotation.x = -Math.PI / 2;
    floor.position.y = 0;
    floor.receiveShadow = true;
    scene.add(floor);

    ambient = new THREE.HemisphereLight(0x9fb6d9, 0x05060a, 0.5);
    scene.add(ambient);

    keyLight = new THREE.DirectionalLight(0xfff5e8, 1.1);
    keyLight.position.set(4.5, 6.5, 5.5);
    keyLight.castShadow = !isMobile;
    if (keyLight.castShadow) {
      keyLight.shadow.mapSize.set(1024, 1024);
      keyLight.shadow.camera.left = -8;
      keyLight.shadow.camera.right = 8;
      keyLight.shadow.camera.top = 8;
      keyLight.shadow.camera.bottom = -8;
      keyLight.shadow.bias = -0.0005;
    }
    scene.add(keyLight);

    fillLight = new THREE.DirectionalLight(0x7f9dff, 0.35);
    fillLight.position.set(-5, 3.5, -3);
    scene.add(fillLight);

    rimLight = new THREE.DirectionalLight(0xffffff, 0.5);
    rimLight.position.set(0, 4.5, -6);
    scene.add(rimLight);

    // Post processing
    composer = new EffectComposer(renderer);
    composer.addPass(new RenderPass(scene, camera));
    bloomPass = new UnrealBloomPass(
      new THREE.Vector2(w, h),
      isMobile ? 0.24 : 0.38,
      0.42,
      0.92
    );
    composer.addPass(bloomPass);
    composer.addPass(new OutputPass());

    threeReady = true;
    clock = new THREE.Clock();

    // Build the current product
    buildCurrentProduct(true);

    // Visibility + resize
    if ('IntersectionObserver' in window) {
      new IntersectionObserver((entries) => {
        isVisible = entries.some((e) => e.isIntersecting);
      }, { threshold: 0.01 }).observe(stage);
    } else {
      isVisible = true;
    }

    resizeObserver = new ResizeObserver(() => resize());
    resizeObserver.observe(stage);
    window.addEventListener('resize', resize);

    resize();
    renderer.setAnimationLoop(animate);
    requestAnimationFrame(() => {
      loader?.classList.add('is-hidden');
      stage.closest('.studio-shell')?.classList.remove('no-webgl');
    });
  } catch (err) {
    console.error('Light Studio failed:', err);
    stage.closest('.studio-shell')?.classList.add('no-webgl');
    loader?.classList.add('is-hidden');
  }
}

function resize() {
  if (!renderer || !camera || !stage) return;
  const w = stage.clientWidth;
  const h = stage.clientHeight;
  if (!w || !h) return;
  renderer.setSize(w, h, false);
  composer?.setSize(w, h);
  camera.aspect = w / h;
  camera.updateProjectionMatrix();
}

function disposeGroup(group) {
  const materials = new Set();
  group.traverse((obj) => {
    if (obj.geometry) obj.geometry.dispose();
    if (obj.material) {
      if (Array.isArray(obj.material)) obj.material.forEach((m) => materials.add(m));
      else materials.add(obj.material);
    }
  });
  materials.forEach((m) => {
    // Keep shared textures alive (glow/shadow are global)
    m.dispose?.();
  });
}

function buildCurrentProduct(initial = false) {
  if (!scene) return;
  const p = getProduct();

  if (currentGroup) {
    scene.remove(currentGroup);
    disposeGroup(currentGroup);
    currentGroup = null;
  }

  const group = p.build();
  currentGroup = group;
  scene.add(group);

  setStageTargets(p.stage || 'indoor');

  controls.minDistance = p.camera.min;
  controls.maxDistance = p.camera.max;
  controls.maxPolarAngle = p.camera.polarMax || (Math.PI / 2 - 0.02);
  controls.minAzimuthAngle = p.camera.azimuth?.min ?? -Infinity;
  controls.maxAzimuthAngle = p.camera.azimuth?.max ?? Infinity;

  state.powerLevel = state.power ? 1 : 0;
  applyFinish();
  applyCCT();
  updateLighting();

  if (initial) {
    const pos = cameraPosFor(p, p.camera.pos);
    camera.position.set(...pos);
    controls.target.set(...p.camera.target);
    controls.update();
  } else {
    flyTo(cameraPosFor(p, p.camera.pos), p.camera.target, reduceMotion ? 0 : 720);
  }
}

function flyTo(pos, target, duration = 700) {
  autoRotateBeforeTween = state.autoRotate;
  if (controls) controls.autoRotate = false;
  if (reduceMotion || duration === 0) {
    camera.position.set(pos[0], pos[1], pos[2]);
    controls.target.set(target[0], target[1], target[2]);
    controls.update();
    cameraTween = null;
  } else {
    cameraTween = {
      fromPos: camera.position.clone(),
      toPos: new THREE.Vector3(pos[0], pos[1], pos[2]),
      fromTarget: controls.target.clone(),
      toTarget: new THREE.Vector3(target[0], target[1], target[2]),
      start: performance.now(),
      duration
    };
  }
}

/* ---------------- Apply state ---------------- */

function applyFinish() {
  const finish = getFinish();
  const label = $('#finishLabel');
  if (label) label.textContent = finish.label;
  if (!currentGroup) return;
  currentGroup.userData.bodyMeshes.forEach((mesh) => {
    const mats = Array.isArray(mesh.material) ? mesh.material : [mesh.material];
    mats.forEach((m) => {
      if (!m?.color) return;
      m.color.set(finish.color);
      m.metalness = finish.metalness;
      m.roughness = finish.roughness;
    });
  });
}

function applyCCT() {
  const color = new THREE.Color(CCT_HEX[state.cct] || '#ffb46b');
  const label = $('#cctLabel');
  if (label) label.textContent = `${faPlain(state.cct)}K`;
  updateCCTReadout();

  if (!currentGroup) return;

  currentGroup.userData.ledMeshes.forEach((mesh) => {
    const mats = Array.isArray(mesh.material) ? mesh.material : [mesh.material];
    mats.forEach((m) => {
      if (m?.emissive) m.emissive.copy(color);
    });
  });

  currentGroup.userData.lights.forEach((entry) => {
    entry.light.color?.copy(color);
  });

  currentGroup.userData.glows.forEach((entry) => {
    entry.mesh.material?.color?.copy(color);
  });
}

function updateLighting() {
  if (!currentGroup || !renderer) return;
  const level = state.powerLevel * state.dim;

  currentGroup.userData.ledMeshes.forEach((mesh) => {
    const mats = Array.isArray(mesh.material) ? mesh.material : [mesh.material];
    mats.forEach((m) => {
      if (typeof m.emissiveIntensity === 'number') {
        m.emissiveIntensity = (mesh.userData.baseEmissive ?? 2.4) * level;
      }
    });
  });

  currentGroup.userData.lights.forEach((entry) => {
    entry.light.intensity = entry.base * level;
  });

  currentGroup.userData.glows.forEach((entry) => {
    entry.mesh.material.opacity = entry.base * level;
  });

  if (ambient) ambient.intensity = 0.18 + 0.32 * level;
  if (keyLight) keyLight.intensity = 0.78 + 0.4 * level;
  if (fillLight) fillLight.intensity = 0.24 + 0.18 * level;
  if (rimLight) rimLight.intensity = 0.22 + 0.3 * level;

  renderer.toneMappingExposure = 0.9 + 0.1 * level;
}

function selectProduct(id, options = {}) {
  const p = PRODUCTS[id];
  if (!p) return;
  state.product = id;

  if (!p.finishes.some((f) => f.id === state.finish)) state.finish = p.finishes[0].id;
  if (!p.ccts.includes(state.cct)) state.cct = p.ccts[0];

  renderProductUI();
  updatePowerUI();
  updateDimUI();
  updateURL();

  if (threeReady) buildCurrentProduct(false);
  if (options.scroll) {
    $('#studio')?.scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth', block: 'start' });
  }
}

/* ---------------- Animation ---------------- */

function animate(now) {
  if (!renderer || !isVisible) return;
  const dt = Math.min(clock?.getDelta() || 0.016, 0.05);

  if (controls) controls.update();

  if (cameraTween) {
    const t = clamp((now - cameraTween.start) / cameraTween.duration, 0, 1);
    const e = t < 0.5 ? 4 * t * t * t : 1 - Math.pow(-2 * t + 2, 3) / 2;
    camera.position.lerpVectors(cameraTween.fromPos, cameraTween.toPos, e);
    controls.target.lerpVectors(cameraTween.fromTarget, cameraTween.toTarget, e);
    controls.update();
    if (t >= 1) {
      cameraTween = null;
      if (controls) controls.autoRotate = autoRotateBeforeTween && state.autoRotate;
    }
  }

  state.powerLevel = THREE.MathUtils.damp(
    state.powerLevel,
    state.power ? 1 : 0,
    6.5,
    dt
  );

  updateLighting();

  // stage color easing
  if (skyMat) {
    skyMat.uniforms.topColor.value.lerp(stageTargets.skyTop, 0.045);
    skyMat.uniforms.bottomColor.value.lerp(stageTargets.skyBottom, 0.045);
  }
  if (floorMat) floorMat.color.lerp(stageTargets.floor, 0.045);
  if (scene?.fog) scene.fog.color.lerp(stageTargets.fog, 0.045);

  composer?.render();
}

/* ---------------- UI bindings ---------------- */

function initMobileTabs() {
  const shell = $('.studio-shell');
  const tabs = $$('.studio-mobile-tab');
  if (!shell || !tabs.length) return;

  const setTab = (name, focus = false) => {
    shell.dataset.mobileTab = name;
    tabs.forEach((tab) => {
      const active = tab.dataset.studioTab === name;
      tab.classList.toggle('is-active', active);
      tab.setAttribute('aria-selected', String(active));
      tab.tabIndex = active ? 0 : -1;
      if (active && focus) tab.focus();
    });
  };

  tabs.forEach((tab, index) => {
    tab.addEventListener('click', () => setTab(tab.dataset.studioTab));

    tab.addEventListener('keydown', (event) => {
      if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
      event.preventDefault();

      let next = index;
      if ('ArrowLeft' === event.key) next = (index + 1) % tabs.length;
      if ('ArrowRight' === event.key) next = (index - 1 + tabs.length) % tabs.length;
      if ('Home' === event.key) next = 0;
      if ('End' === event.key) next = tabs.length - 1;

      setTab(tabs[next].dataset.studioTab, true);
    });
  });

  setTab('products');
}

function bindUI() {
  const rotateButton = $('#studioRotate');  if (rotateButton) {
    rotateButton.setAttribute('aria-pressed', String(state.autoRotate));
    rotateButton.classList.toggle('is-active', state.autoRotate);
  }

  const power = $('#studioPower');
  power?.addEventListener('click', () => {
    state.power = !state.power;
    updatePowerUI();
  });

  const dim = $('#dimRange');
  dim?.addEventListener('input', () => {
    state.dim = Number(dim.value) / 100;
    updateDimUI();
  });

  $('#studioRotate')?.addEventListener('click', (e) => {
    state.autoRotate = !state.autoRotate;
    if (controls) controls.autoRotate = state.autoRotate;
    e.currentTarget.setAttribute('aria-pressed', String(state.autoRotate));
    e.currentTarget.classList.toggle('is-active', state.autoRotate);
  });

  // Fullscreen: native API first, CSS pseudo-fullscreen as a robust fallback
  // (needed inside Elementor iframes where requestFullscreen may be blocked).
  const stageEl = $('#studioStage');
  const isPseudo = () => stageEl?.classList.contains('is-pseudo-fullscreen');
  const updateFullButton = () => {
    const btn = $('#studioFull');
    const on = !!document.fullscreenElement || isPseudo();
    btn?.setAttribute('aria-pressed', String(on));
    const use = btn?.querySelector('use');
    if (use) use.setAttribute('href', on ? '#pn-i-close' : '#pn-i-expand');
  };
  const hostSection = stageEl?.closest('.studio-section');
  const hostShell = stageEl?.closest('.studio-shell');
  const exitPseudo = () => {
    stageEl?.classList.remove('is-pseudo-fullscreen');
    hostSection?.classList.remove('is-fullscreen-host');
    hostShell?.classList.remove('is-fullscreen-host');
    document.body.classList.remove('studio-fullscreen');
    setTimeout(resize, 80);
    updateFullButton();
  };
  const enterPseudo = () => {
    stageEl?.classList.add('is-pseudo-fullscreen');
    hostSection?.classList.add('is-fullscreen-host');
    hostShell?.classList.add('is-fullscreen-host');
    document.body.classList.add('studio-fullscreen');
    setTimeout(resize, 80);
    updateFullButton();
  };
  const toggleFullscreen = async () => {
    if (!stageEl) return;
    if (document.fullscreenElement) {
      await document.exitFullscreen?.();
      return;
    }
    if (isPseudo()) {
      exitPseudo();
      return;
    }
    const request = stageEl.requestFullscreen || stageEl.webkitRequestFullscreen || stageEl.msRequestFullscreen;
    if (request) {
      try {
        await request.call(stageEl);
        setTimeout(resize, 120);
        updateFullButton();
        return;
      } catch (_) {
        /* fall through to CSS fullscreen */
      }
    }
    enterPseudo();
  };
  $('#studioFull')?.addEventListener('click', toggleFullscreen);
  document.addEventListener('fullscreenchange', () => {
    setTimeout(resize, 100);
    updateFullButton();
  });
  document.addEventListener('keydown', (e) => {
    if (e.key !== 'Escape') return;
    if (isPseudo()) exitPseudo();
    else if (document.fullscreenElement) document.exitFullscreen?.();
  });

  $$('.studio-presets button').forEach((btn) => {
    btn.addEventListener('click', () => {
      const view = btn.dataset.view;
      const p = getProduct();
      const target = p.camera.target;
      const pos = p.presets[view] || p.camera.pos;
      flyTo(cameraPosFor(p, pos), target, 620);
      $$('.studio-presets button').forEach((b) => b.classList.toggle('is-active', b === btn));
    });
  });

  // keyboard controls
  canvas?.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' || e.key === ' ') {
      e.preventDefault();
      state.power = !state.power;
      updatePowerUI();
      return;
    }
    if (!camera || !controls) return;

    const step = e.shiftKey ? 0.24 : 0.12;
    const offset = camera.position.clone().sub(controls.target);
    const spherical = new THREE.Spherical().setFromVector3(offset);

    if (e.key === 'ArrowLeft' || e.key === 'ArrowRight' || e.key === 'ArrowUp' || e.key === 'ArrowDown') {
      e.preventDefault();
      state.autoRotate = false;
      if (controls) controls.autoRotate = false;
      $('#studioRotate')?.setAttribute('aria-pressed', 'false');
      if (e.key === 'ArrowLeft') spherical.theta -= step;
      if (e.key === 'ArrowRight') spherical.theta += step;
      if (e.key === 'ArrowUp') spherical.phi = clamp(spherical.phi - step, 0.2, Math.PI / 2 - 0.03);
      if (e.key === 'ArrowDown') spherical.phi = clamp(spherical.phi + step, 0.2, Math.PI / 2 - 0.03);
      offset.setFromSpherical(spherical);
      camera.position.copy(controls.target).add(offset);
      controls.update();
    }
  });
}

/* ---------------- Init ---------------- */

function init() {
  stage = $('#studioStage');
  loader = $('#studioLoader');
  fallbackImg = $('#studioFallbackImg');
  if (!stage) return;

  // Read URL state
  try {
    const params = new URLSearchParams(window.location.search);
    const p = params.get('p');
    const finish = params.get('finish');
    const cct = Number(params.get('cct'));
    const power = params.get('power');
    if (p && PRODUCTS[p]) state.product = p;
    if (finish) state.finish = finish;
    if (cct) state.cct = cct;
    if (power === 'off') state.power = false;
  } catch (_) { /* noop */ }

  const p = getProduct();
  if (!p.finishes.some((f) => f.id === state.finish)) state.finish = p.finishes[0].id;
  if (!p.ccts.includes(state.cct)) state.cct = p.ccts[0];

  renderRail();
  renderProductUI();
  updatePowerUI();
  updateDimUI();
  bindUI();
  initMobileTabs();

  // Bridge from the alphabet section
  window.addEventListener('pn:select-product', (e) => {
    selectProduct(e.detail?.id);
  });

  // Debug / external control hook
  window.PNStudio = {
    select: selectProduct,
    renderOnce: () => {
      isVisible = true;
      updateLighting();
      composer?.render();
    },
    get state() { return state; },
    get camera() { return camera; },
    get controls() { return controls; },
    get product() { return getProduct(); }
  };

  // Lazy init 3D when near viewport
  if ('IntersectionObserver' in window) {
    stageObserver = new IntersectionObserver((entries) => {
      if (entries.some((entry) => entry.isIntersecting)) {
        stageObserver.disconnect();
        initThree();
      }
    }, { rootMargin: '500px 0px' });
    stageObserver.observe(stage);
  } else {
    initThree();
  }
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', init);
} else {
  init();
}

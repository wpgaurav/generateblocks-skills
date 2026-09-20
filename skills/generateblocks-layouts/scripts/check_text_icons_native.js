/* Read-only native Text icon regression probe. Run in an editor with GB loaded.
 * No page blocks or WordPress records are changed. Returns a JSON report. */
(async () => {
  const wpb = window.wp?.blocks;
  const compile = window.gbp?.stylesBuilder?.getCss || window.gb?.stylesBuilder?.getCss;
  if (!wpb?.getBlockType("generateblocks/text") || !compile) throw new Error("Open a matching GenerateBlocks editor before running this probe.");
  const svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 13l4 4L19 7"></path></svg>';
  const variants = [
    ['before', {tagName:'h2',content:'Fast delivery'}],
    ['after', {tagName:'h3',content:'More details',iconLocation:'after'}],
    ['icon-only', {tagName:'button',iconOnly:true,htmlAttributes:{'aria-label':'Confirm',type:'button'}}],
    ['unstyled', {tagName:'h2',content:'Unstyled heading'}],
    ['empty-label', {tagName:'p',content:''}],
    ['rich-text', {tagName:'h2',content:'Fast <em>and</em> reliable &amp; clear'}],
    ['without-icon', {tagName:'h2',content:'Plain heading',icon:''}],
  ];
  const results = [];
  for (let i=0; i<variants.length; i++) {
    const [name,extra] = variants[i];
    const attrs = {uniqueId:'icon-4821-'+(i+1),styles:{display:'inline-flex',alignItems:'center',columnGap:'0.5em','.gb-shape svg':{width:'1em',height:'1em'}},icon:svg,...extra};
    if(name==='unstyled') delete attrs.styles;
    if(attrs.styles) attrs.css=await compile('.gb-text-'+attrs.uniqueId,attrs.styles);
    const serialized=wpb.serialize(wpb.createBlock('generateblocks/text',attrs));
    const parsed=wpb.parse(serialized);
    const roundtrip=wpb.serialize(parsed);
    const el=new DOMParser().parseFromString(serialized,'text/html').body.firstElementChild;
    results.push({name,serialized,valid:parsed.every(b=>b.isValid),roundtrip,roundtripValid:wpb.parse(roundtrip).every(b=>b.isValid),label:el.querySelector('span.gb-text')?.textContent||(!attrs.icon?el.textContent:''),icons:el.querySelectorAll('.gb-shape svg').length});
  }
  const labels = ['Fast delivery','More details','','Unstyled heading','','Fast and reliable & clear','Plain heading'];
  for (const [index,item] of results.entries()) {
    if (!item.valid || !item.roundtripValid || item.serialized !== item.roundtrip) throw new Error("Native round trip failed: " + item.name);
    if (item.label !== labels[index] || item.icons !== (item.name === 'without-icon' ? 0 : 1)) throw new Error("Native content changed: " + item.name);
  }
  return JSON.stringify({variants:results,dirty:wp.data.select('core/editor').isEditedPostDirty()});
})()

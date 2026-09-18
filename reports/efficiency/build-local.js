/* Benchmark only. Run in the source page's real editor on localhost.
 * Reads existing shared records; creates only a local-style page and form.
 */
window.gbEfficiencyBuild = async function(sourcePageId, create=true, pageId=null, formId=null) {
  if(location.hostname!=='localhost') throw Error('Local benchmark only');
  const api=wp.apiFetch, {parse,serialize,createBlock}=wp.blocks;
  const source=await api({path:`/wp/v2/pages/${sourcePageId}?context=edit`});
  const records=await api({path:'/wp/v2/gblocks_styles?context=edit&per_page=100'});
  const root=await api({path:'/generateblocks-pro/v1/styles/root'});
  const bySelector=Object.fromEntries(records.map(r=>[r.gb_style_selector,r.gb_style_data]));
  const baseVars=Object.fromEntries(Object.entries(root.styles).filter(([k])=>k.startsWith('--')));
  const rootQueries=Object.entries(root.styles).filter(([k,v])=>k.startsWith('@')&&typeof v==='object');
  const merge=(a,b)=>{const r=structuredClone(a);for(const [k,v] of Object.entries(b||{}))r[k]=v&&typeof v==='object'&&!Array.isArray(v)?merge(r[k]||{},v):v;return r;};
  function resolve(value,vars,depth=0){
    if(depth>20)throw Error('Variable cycle');
    if(typeof value==='object')return Object.fromEntries(Object.entries(value).map(([k,v])=>[k,resolve(v,vars,depth)]));
    if(typeof value!=='string')return value;
    const replaced=value.replace(/var\((--[\w-]+)\)/g,(m,n)=>{if(!(n in vars))throw Error('Missing variable '+n);return vars[n];});
    return replaced===value?value:resolve(replaced,vars,depth+1);
  }
  function diff(a,b){const out={};for(const [k,v] of Object.entries(b)){if(k.startsWith('@'))continue;if(v&&typeof v==='object'){const d=diff(a[k]||{},v);if(Object.keys(d).length)out[k]=d;}else if(v!==a[k])out[k]=v;}return out;}
  function localize(styles){
    const out=resolve(styles,baseVars);
    for(const [query,overrides] of rootQueries){const alternate=resolve(styles,{...baseVars,...overrides});const changes=diff(out,alternate);if(Object.keys(changes).length)out[query]=merge(changes,alternate[query]||{});}
    return out;
  }
  const walk=blocks=>blocks.flatMap(b=>[b,...walk(b.innerBlocks||[])]);
  const sourceBlocks=parse(source.content.raw);
  const sourceFormId=walk(sourceBlocks).find(b=>b.name==='generateblocks-pro/form-render').attributes.formId;
  const sourceForm=await api({path:`/wp/v2/gblocks-forms/${sourceFormId}?context=edit`});
  if(create){
    const p=await api({path:'/wp/v2/pages',method:'POST',data:{title:'Fieldwork / Local styling efficiency benchmark',status:'draft'}});pageId=p.id;
    const f=await api({path:'/wp/v2/gblocks-forms',method:'POST',data:{title:'Efficiency local form',status:'draft'}});formId=f.id;
  }
  if(!pageId||!formId)throw Error('Actual destination IDs required');
  let owner=pageId,sequence=0;
  const styled=async(name,attrs,children,styles)=>{
    const id=`bench-${owner}-${++sequence}`,type=name.split('/')[1];
    attrs={...attrs,uniqueId:id};delete attrs.globalClasses;delete attrs.css;delete attrs.styles;
    if(attrs.htmlAttributes&&Object.keys(attrs.htmlAttributes).length===0)delete attrs.htmlAttributes;
    if(Object.keys(styles).length){
      attrs.styles=localize(styles);
      const probe=serialize([createBlock(name,attrs,children)]);
      const classes=[...probe.matchAll(/class="([^"]*)"/g)].flatMap(m=>m[1].split(/\s+/)).filter(c=>c.endsWith('-'+id));
      if(classes.length!==1)throw Error('Cannot resolve native selector for '+name);
      attrs.css=await gbp.stylesBuilder.getCss('.'+classes[0],attrs.styles);
    }
    return createBlock(name,attrs,children);
  };
  async function convert(block){
    const attrs={...block.attributes,...block.attributes.htmlAttributes?{htmlAttributes:{...block.attributes.htmlAttributes}}:{}},children=[];
    for(const child of block.innerBlocks||[])children.push(await convert(child));
    if(block.name==='generateblocks-pro/form-render')return createBlock(block.name,{formId});
    if(block.name==='core/table'){
      delete attrs.className;
      return styled('generateblocks/element',{tagName:'div'},[createBlock(block.name,attrs,children)],bySelector['.kit-table']);
    }
    if(!block.name.startsWith('generateblocks'))return createBlock(block.name,attrs,children);
    let styles={};const tag=attrs.tagName;
    if(['h1','h2','h3'].includes(tag))styles=merge(styles,bySelector[':is(h1,h2,h3)']);
    if(['h1','h2','h3','p','a'].includes(tag))styles=merge(styles,bySelector[tag]);
    for(const className of attrs.globalClasses||[])styles=merge(styles,bySelector['.'+className]);
    if(tag==='button'&&attrs.htmlAttributes?.type==='submit')styles=merge(styles,bySelector['.kit-button']);
    if(attrs.content&&(/<a\b|link:post/.test(String(attrs.content)))){
      const link=bySelector.a;styles['& a']=Object.fromEntries(Object.entries(link).filter(([k])=>!k.startsWith('&')));
      for(const [k,v] of Object.entries(link))if(k.startsWith('&'))styles['& a'+k.slice(1)]=v;
    }
    styles=merge(styles,attrs.styles||{});
    if(attrs.htmlAttributes?.href)attrs.htmlAttributes.href=attrs.htmlAttributes.href.replace(`?page_id=${sourcePageId}#`,`?page_id=${pageId}#`);
    return styled(block.name,attrs,children,styles);
  }
  async function produce(){
    owner=formId;sequence=0;const fb=[];for(const b of parse(sourceForm.content.raw))fb.push(await convert(b));
    owner=pageId;sequence=0;const pb=[];for(const b of sourceBlocks)pb.push(await convert(b));
    const wrapper=await styled('generateblocks/element',{tagName:'div'},pb,{...bySelector.body,minHeight:'100vh'});
    return {page:serialize([wrapper]),form:serialize(fb)};
  }
  const samples=[];let content;
  for(let i=0;i<12;i++){const start=performance.now();content=await produce();const duration=performance.now()-start;if(i>=2)samples.push(duration);}
  const serializeSamples=[];
  for(let i=0;i<12;i++){const start=performance.now();serialize(parse(source.content.raw));const duration=performance.now()-start;if(i>=2)serializeSamples.push(duration);}
  const localBlocks=walk(parse(content.page)),localFormBlocks=walk(parse(content.form));
  const invalid=[...localBlocks,...localFormBlocks].filter(b=>!b.isValid).map(b=>b.name);
  if(invalid.length)throw Error('Invalid local blocks '+invalid.join(','));
  if([...localBlocks,...localFormBlocks].some(b=>b.attributes.globalClasses?.length))throw Error('Unexpected shared classes');
  if(/var\(--kit-/.test(content.page+content.form))throw Error('Unresolved kit token');
  {
    await api({path:`/wp/v2/gblocks-forms/${formId}`,method:'POST',data:{content:content.form,status:'publish',meta:sourceForm.meta}});
    await api({path:`/wp/v2/pages/${pageId}`,method:'POST',data:{content:content.page,status:'publish'}});
  }
  const saved=await api({path:`/wp/v2/pages/${pageId}?context=edit`});
  const savedForm=await api({path:`/wp/v2/gblocks-forms/${formId}?context=edit`});
  const bytes=s=>new TextEncoder().encode(s).length;
  const cssBytes=blocks=>blocks.reduce((n,b)=>n+bytes(b.attributes.css||''),0);
  let affected=0;for(const b of [...localBlocks,...localFormBlocks]){const visit=o=>{for(const v of Object.values(o||{})){if(v&&typeof v==='object')visit(v);else if(typeof v==='string'&&v.includes(baseVars['--kit-accent']))affected++;}};visit(b.attributes.styles);}
  const used=new Set(walk([...sourceBlocks,...parse(sourceForm.content.raw)]).flatMap(b=>b.attributes.globalClasses||[]));
  return {sourcePageId,pageId,formId,sharedStyleRecords:records.length,tokenCount:root.tokens.length,
    localBlocks:localBlocks.length,sharedBlocks:walk(sourceBlocks).length,invalid,
    byteStable:saved.content.raw===content.page&&savedForm.content.raw===content.form,
    pageBytes:{shared:bytes(source.content.raw),local:bytes(content.page)},
    formBytes:{shared:bytes(sourceForm.content.raw),local:bytes(content.form)},
    localCssBytes:cssBytes([...localBlocks,...localFormBlocks]),
    globalCssBytes:records.reduce((n,r)=>n+bytes(r.gb_style_css||'')+bytes(r.gb_style_targets_css||''),0),
    localAccentDeclarationValues:affected,referencedClasses:used.size,
    timingMs:{localCompileAndSerialize:samples,sharedParseAndSerialize:serializeSamples},
    content,sourceContent:source.content.raw,sourceFormContent:sourceForm.content.raw};
};

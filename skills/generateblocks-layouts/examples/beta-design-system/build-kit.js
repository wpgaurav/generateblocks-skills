/* Run in the real block editor of a disposable localhost site after setup-local.php.
 * Uses the installed plugins' serializers, CSS compiler, and native importer.
 * After explicit shared-system opt-in, call await gbBetaKit.build({pageId, postIds, preset:'paper'|'ink', sharedSystemRequested:true}).
 */
window.gbBetaKit = (() => {
  const v = name => `var(--kit-${name})`;
  const media = '@media (max-width:767px)';
  const root = {
    '--kit-accent':'#943f28', '--kit-background':'#faf7f1', '--kit-surface':'#eee8de',
    '--kit-text':'#24231f', '--kit-muted':'#615d54', '--kit-border':'#ccc4b7',
    '--kit-on-accent':'#ffffff', '--kit-accent-soft':'color-mix(in oklch,var(--kit-accent) 9%,var(--kit-background))',
    '--kit-body-font':'system-ui,sans-serif', '--kit-heading-font':'Georgia,serif',
    '--kit-body-size':'1.0625rem', '--kit-display-size':'clamp(2.6rem,1.35rem + 4.5vw,5.5rem)',
    '--kit-heading-size':'clamp(1.9rem,1.25rem + 2vw,3rem)',
    '--kit-section-space':'5rem', '--kit-gap':'2rem', '--kit-gutter':'clamp(1.25rem,4vw,3rem)',
    '--kit-radius':'4px', '--kit-reading-width':'62ch', '--gb-container-width':'1160px',
    [media]:{'--kit-section-space':'3rem','--kit-gap':'1.25rem'}
  };
  const ink = {
    '--kit-accent':'#a8e1ca','--kit-background':'#102c28','--kit-surface':'#183c36',
    '--kit-text':'#f1f6ee','--kit-muted':'#b7ccc3','--kit-border':'#406058',
    '--kit-on-accent':'#102c28','--kit-heading-font':'system-ui,sans-serif',
    '--kit-display-size':'clamp(2.6rem,1.4rem + 4vw,5rem)','--kit-section-space':'4.5rem'
  };
  const definitions = [
    ['body','Foundation',{backgroundColor:v('background'),color:v('text'),fontFamily:v('body-font'),fontSize:v('body-size'),lineHeight:'1.65'}],
    [':is(h1,h2,h3)','Foundation',{fontFamily:v('heading-font'),fontWeight:'500',lineHeight:'1.1',textWrap:'balance',marginTop:'0',marginBottom:'1.25rem'}],
    ['h1','Foundation',{fontSize:v('display-size'),maxWidth:'16ch'}],
    ['h2','Foundation',{fontSize:v('heading-size')}],
    ['h3','Foundation',{fontSize:'1.5rem'}],
    ['p','Foundation',{marginTop:'0',marginBottom:'1.25rem'}],
    ['a','Foundation',{color:v('accent'),textUnderlineOffset:'.2em','&:focus-visible':{outline:`2px solid ${v('accent')}`,outlineOffset:'4px'}}],
    ['.kit-rail','Layout',{maxWidth:'var(--gb-container-width)',marginLeft:'auto',marginRight:'auto',paddingLeft:v('gutter'),paddingRight:v('gutter')}],
    ['.kit-section','Layout',{paddingTop:v('section-space'),paddingBottom:v('section-space'),borderBottom:`1px solid ${v('border')}`}],
    ['.kit-split','Layout',{display:'grid',gridTemplateColumns:'minmax(0,1.3fr) minmax(0,1fr)',gap:v('gap'),alignItems:'center',[media]:{gridTemplateColumns:'minmax(0,1fr)'}}],
    ['.kit-stack','Layout',{display:'flex',flexDirection:'column',gap:v('gap')}],
    ['.kit-actions','Layout',{display:'flex',flexWrap:'wrap',gap:'1rem',alignItems:'center'}],
    ['.kit-lead','Type',{fontSize:'1.25rem',maxWidth:v('reading-width'),color:v('muted')}],
    ['.kit-muted','Type',{color:v('muted'),fontSize:'.9375rem'}],
    ['.kit-button','Components',{display:'inline-flex',alignItems:'center',justifyContent:'center',minHeight:'48px',padding:'0.75rem 1.25rem',border:'0',borderRadius:v('radius'),backgroundColor:v('accent'),color:v('on-accent'),textDecoration:'none',fontWeight:'650',cursor:'pointer','&:hover':{backgroundColor:`color-mix(in oklch,${v('accent')} 88%,${v('text')})`},'&:focus-visible':{outline:`2px solid ${v('accent')}`,outlineOffset:'4px'}},['.kit-form button[type="submit"]']],
    ['.kit-specimen','Components',{backgroundColor:v('accent-soft'),padding:'clamp(1.5rem,3vw,3rem)',borderRadius:v('radius')}],
    ['.kit-table','Components',{overflowX:'auto','& table':{borderCollapse:'collapse',width:'100%',minWidth:'32rem'},'& th':{textAlign:'left',fontWeight:'650',padding:'1rem',borderBottom:`1px solid ${v('border')}`},'& td':{padding:'1rem',borderBottom:`1px solid ${v('border')}`,verticalAlign:'top'}}],
    ['.kit-story','Components',{paddingTop:'1.25rem',borderTop:`1px solid ${v('border')}`,minWidth:'0'}],
    ['.kit-faq-item','Components',{borderBottom:`1px solid ${v('border')}`}],
    ['.kit-faq-toggle','Components',{display:'flex',width:'100%',justifyContent:'space-between',textAlign:'left',padding:'1.25rem 0',border:'0',backgroundColor:'transparent',color:v('text'),fontSize:'1.125rem',cursor:'pointer','&:focus-visible':{outline:`2px solid ${v('accent')}`,outlineOffset:'4px'}}],
    ['.kit-faq-content','Components',{paddingBottom:'1.25rem',maxWidth:v('reading-width')}],
    ['.kit-form','Components',{display:'flex',flexDirection:'column',gap:'1.25rem','& label':{display:'block',fontWeight:'600',marginBottom:'.5rem'},'& input':{width:'100%',minHeight:'48px',padding:'.75rem',border:`1px solid ${v('border')}`,borderRadius:v('radius'),backgroundColor:v('background'),color:v('text')},'& input:focus-visible':{outline:`2px solid ${v('accent')}`,outlineOffset:'3px'}}],
    ['.kit-nav','Components',{display:'flex',flexWrap:'wrap',justifyContent:'space-between',alignItems:'center',gap:'1rem',paddingTop:'1.5rem',paddingBottom:'1.5rem',borderBottom:`1px solid ${v('border')}`}]
  ];
  function artifact(preset='paper') {
    const styles=structuredClone(root);
    if(preset==='ink') Object.assign(styles,ink);
    const tokens=Object.keys(styles).filter(n=>n.startsWith('--')).map(name=>{
      const color=/accent|background|surface|text|muted|border/.test(name);
      const type=color?'color':name.includes('font')?'text':'unit';
      return {name,type,label:name.replace(/^--(?:kit-|gb-)/,'').replaceAll('-',' '),category:color?'Color':type==='text'?'Typography':'Size',scope:color?['color','backgroundColor','borderColor']:type==='text'?['fontFamily']:['fontSize','gap','paddingTop','paddingBottom','maxWidth']};
    });
    return {kind:'generateblocks/design-system',version:1,globalStyles:[{kind:'generateblocks/global-style',version:1,selector:':root',isPartial:false,styles},...definitions.map(([selector,category,styles,targets])=>({kind:'generateblocks/global-style',version:1,selector,category,isPartial:false,styles,...targets?{targets}:{}}))],designTokens:tokens};
  }
  async function loadImporter() {
    if(window.generateBlocksProDesignSystem) return;
    await new Promise((resolve,reject)=>{const s=document.createElement('script');s.src='/wp-content/plugins/generateblocks-pro/dist/design-system-import.js';s.onload=resolve;s.onerror=reject;document.head.append(s);});
  }
  async function build({pageId,postIds,preset='paper',importStyles=true,formId=null,sharedSystemRequested=false}) {
    if(sharedSystemRequested!==true) throw Error('This starter adds shared styles and token dependencies. Prompt the user first, then pass sharedSystemRequested:true only after explicit opt-in.');
    if(!['localhost','127.0.0.1','[::1]'].includes(location.hostname)) throw Error('Local test sites only.');
    if(!Number.isInteger(pageId)||pageId<1) throw Error('Resolve the real page ID first.');
    await loadImporter();
    const design=artifact(preset);
    const imported=importStyles?await window.generateBlocksProDesignSystem.importMissing(design):null;
    const {createBlock:b,serialize,parse}=wp.blocks;
    const api=wp.apiFetch;
    let sequence=0,owner=pageId;
    const uid=slug=>`${slug}-${owner}-${++sequence}`;
    const gb=(type,attrs={},children=[])=>b(`generateblocks/${type}`,{uniqueId:uid(type),...attrs},children);
    const pro=(type,attrs={},children=[])=>b(`generateblocks-pro/${type}`,{uniqueId:uid(type),...attrs},children);
    const text=(content,tagName='p',classes=[])=>gb('text',{tagName,content,globalClasses:classes});
    const el=(children,classes=[],tagName='div',htmlAttributes={})=>gb('element',{tagName,globalClasses:classes,...Object.keys(htmlAttributes).length?{htmlAttributes}:{}},children);
    const link=(label,href,classes=[])=>el([text(label,'span')],classes,'a',{href});
    const section=(children,id)=>el([el(children,['kit-rail'])],['kit-section'],'section',{id});
    const origin=location.origin;
    const form=formId?await api({path:`/wp/v2/gblocks-forms/${formId}?context=edit`}):await api({path:'/wp/v2/gblocks-forms',method:'POST',data:{title:'Kit local demo form',status:'draft'}});
    owner=form.id;sequence=0;
    const formBlocks=[pro('form',{tagName:'form',globalClasses:['kit-form']},[
      pro('form-field',{fieldType:'email',fieldName:'email',isRequired:true},[
        pro('form-field-label',{content:'Email address',tagName:'label'}),
        pro('form-field-control',{placeholder:'you@example.com',htmlAttributes:{autocomplete:'email'}})
      ]),
      text('Save demo request','button')
    ])];
    formBlocks[0].innerBlocks[1].attributes.htmlAttributes={type:'submit'};
    const formContent=serialize(formBlocks);
    await api({path:`/wp/v2/gblocks-forms/${form.id}`,method:'POST',data:{status:'publish',content:formContent,meta:{_gb_form:{config:{formType:'contact',actions:['email'],emailTo:'demo@example.test',emailFromEmail:'demo@example.test',emailFromName:'Kit demo',emailSubject:'Local kit test',storeSubmissions:true,successMessage:'Demo request saved locally. No email was sent.',errorMessage:'Please check the email address and try again.'}}}}});
    owner=pageId;sequence=0;
    const nav=el([text('Fieldwork','span'),link('Try the demo',`${origin}/?page_id=${pageId}#request`)],['kit-rail','kit-nav'],'header');
    const hero=section([el([
      el([text('A good page starts with a shared system.','h1'),text('Fieldwork is a working product-page kit. Change the type, color, and spacing once, then use the same pieces across your next site.','p',['kit-lead']),el([link('Try the demo form',`${origin}/?page_id=${pageId}#request`,['kit-button']),link('Compare the approach',`${origin}/?page_id=${pageId}#compare`)],['kit-actions'])]),
      el([text('One decision. Every page.','h2'),text('Named tokens keep your colors, typography, and spacing together. Shared components turn those decisions into a repeatable page.'),text('This panel, the links, and the form all use the same accent token.','p',['kit-muted'])],['kit-specimen'])
    ],['kit-split'])],'overview');
    const table=b('core/table',{className:'kit-table',head:[{cells:[{content:'When you change…',tag:'th'},{content:'Local values on every block',tag:'th'},{content:'This shared kit',tag:'th'}]}],body:[
      {cells:[{content:'Brand color',tag:'td'},{content:'Find and edit each value',tag:'td'},{content:'Change the accent token',tag:'td'}]},
      {cells:[{content:'Section spacing',tag:'td'},{content:'Repeat desktop and mobile edits',tag:'td'},{content:'Edit one responsive spacing token',tag:'td'}]},
      {cells:[{content:'Button treatment',tag:'td'},{content:'Keep every copy in sync',tag:'td'},{content:'Update one Global Style',tag:'td'}]}
    ]});
    const comparison=section([text('Keep the useful decisions. Lose the repetition.','h2'),table],'compare');
    const stories=section([text('Build from the content outward.','h2'),gb('query',{tagName:'div',query:{post_type:'post',post__in:postIds,posts_per_page:2,orderby:'ID',order:'ASC'}},[
      gb('looper',{tagName:'div',globalClasses:['kit-split']},[gb('loop-item',{tagName:'article',globalClasses:['kit-story']},[text('{{post_title link:post}}','h3'),text('{{post_excerpt length:28}}')])]),
      b('generateblocks/query-no-results',{},[text('New field notes will appear here.')])
    ])],'notes');
    const qa=[['Can I change the design without rebuilding the page?','Yes. This kit uses shared tokens for color, type, and spacing. Global Styles own repeated components; individual blocks keep only their content and layout exceptions.'],['What travels to a second site?','The design-system JSON carries styles and registered tokens. The page builder creates new post-scoped block IDs and maps its form and query records to the destination.'],['Does the demo send an email?','No. This local test captures the mail action and stores only the demo submission. Production delivery needs your own configuration.']];
    const faq=section([text('Before you make it yours.','h2'),pro('accordion',{tagName:'div'},qa.map(([q,a])=>pro('accordion-item',{tagName:'div',globalClasses:['kit-faq-item']},[
      pro('accordion-toggle',{tagName:'button',globalClasses:['kit-faq-toggle']},[text(q,'span'),text('+','span')]),
      pro('accordion-content',{tagName:'div',globalClasses:['kit-faq-content']},[text(a)])
    ])))],'questions');
    const signup=section([el([el([text('Try one real interaction.','h2'),text('Use a test email address to check the form. The request stays in this local demo.','p',['kit-lead'])]),b('generateblocks-pro/form-render',{formId:form.id})],['kit-split'])],'request');
    const footer=el([text('Fieldwork / GenerateBlocks beta kit','p'),text('A local demonstration. No purchase, mailing list, or external delivery.','p',['kit-muted'])],['kit-rail','kit-section'],'footer');
    const blocks=[nav,hero,comparison,stories,faq,signup,footer];
    const content=serialize(blocks);
    const walk=xs=>xs.flatMap(x=>[x,...walk(x.innerBlocks||[])]);
    const parsed=parse(content),invalid=walk(parsed).filter(x=>!x.isValid).map(x=>x.name);
    if(invalid.length) throw Error(`Invalid generated blocks: ${invalid.join(',')}`);
    window.gbBetaKitAttempt={content,formContent,designSystem:design,formId:form.id};
    await api({path:`/wp/v2/pages/${pageId}`,method:'POST',data:{content,status:'publish'}});
    const readback=await api({path:`/wp/v2/pages/${pageId}?context=edit`});
    if(readback.content.raw!==content) throw Error('Stored content differs from serialized content.');
    return {preset,pageId,formId:form.id,imported,designSystem:design,content,formContent,blockCount:walk(parsed).length,invalid,roundtripStable:serialize(parsed)===content,patterns:{hero:serialize([hero]),comparison:serialize([comparison]),faq:serialize([faq]),signup:serialize([signup]),query:serialize([stories]),footer:serialize([footer])}};
  }
  return {artifact,build,loadImporter};
})();

/* ============================================================
   萌图云 MoePic - 全局 JS
   - 夜间模式持久化
   - 图片懒加载
   - Toast 提示
   - 复制链接
   - 拖拽 / 粘贴 / 进度条 上传(Uploader)
   ============================================================ */
(function(){
  'use strict';

  /* ---------- 夜间模式 ---------- */
  const themeBtn = document.getElementById('themeBtn');
  const root = document.documentElement;
  const saved = localStorage.getItem('moepic_theme');
  function applyTheme(t){
    root.setAttribute('data-theme', t);
    if(themeBtn) themeBtn.textContent = t === 'dark' ? '☀️' : '🌙';
  }
  applyTheme(saved || 'light');
  if(themeBtn){
    themeBtn.addEventListener('click', ()=>{
      const cur = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
      localStorage.setItem('moepic_theme', cur);
      applyTheme(cur);
    });
  }

  /* ---------- Toast ---------- */
  const toastEl = document.getElementById('toast') || (()=>{
    const d = document.createElement('div'); d.id='toast'; d.className='toast';
    document.body.appendChild(d); return d;
  })();
  window.toast = function(msg, ok=true){
    if(!toastEl) return; // 容错:找不到提示元素不阻断逻辑
    toastEl.textContent = msg;
    toastEl.style.background = ok ? 'var(--ink)' : '#ff6b81';
    toastEl.classList.add('on');
    clearTimeout(toastEl._t);
    toastEl._t = setTimeout(()=>toastEl.classList.remove('on'), 2200);
  };

  /* ---------- 复制到剪贴板 ---------- */
  window.copyText = function(text){
    if(navigator.clipboard && window.isSecureContext){
      navigator.clipboard.writeText(text).then(()=>window.toast('已复制到剪贴板 ✨'), ()=>window.toast('复制失败',false));
    }else{
      const ta = document.createElement('textarea');
      ta.value = text; ta.style.position='fixed'; ta.style.opacity='0';
      document.body.appendChild(ta); ta.select();
      try{ document.execCommand('copy'); window.toast('已复制到剪贴板 ✨'); }
      catch(e){ window.toast('复制失败',false); }
      document.body.removeChild(ta);
    }
  };
  // 事件委托:所有带 data-copy 属性的按钮点击即复制
  document.addEventListener('click', e=>{
    const btn = e.target.closest('[data-copy]');
    if(btn) window.copyText(btn.dataset.copy);
  });

  /* ---------- 懒加载(IntersectionObserver) ---------- */
  function initLazy(){
    const imgs = document.querySelectorAll('img.lazy');
    if(!('IntersectionObserver' in window)){
      imgs.forEach(i=>{i.src=i.dataset.src;i.classList.add('loaded');});
      return;
    }
    const io = new IntersectionObserver((entries)=>{
      entries.forEach(en=>{
        if(en.isIntersecting){
          const img = en.target;
          img.src = img.dataset.src;
          img.addEventListener('load', ()=>img.classList.add('loaded'), {once:true});
          io.unobserve(img);
        }
      });
    },{rootMargin:'200px'});
    imgs.forEach(i=>io.observe(i));
  }
  document.addEventListener('DOMContentLoaded', initLazy);

  /* ============================================================
     Uploader - 拖拽 / 点击 / 粘贴 + 真实进度(XHR) + 结果展示
     ============================================================ */
  class Uploader {
    constructor(opts){
      this.dropEl   = opts.dropEl;      // 拖拽区域元素
      this.fileInput= opts.fileInput;   // <input type="file">
      this.progressEl= opts.progressEl; // .progress > span
      this.progressBar= opts.progressBar;
      this.resultEl = opts.resultEl;    // 结果容器
      this.extraData= opts.extraData || {}; // 额外表单字段(CSRF等)
      this.onSuccess= opts.onSuccess || function(){};
      this.apiUrl   = opts.apiUrl || '/api/upload.php';
      this.disabled = !!opts.disabled; // 为 true 时禁止上传(游客且未开放)
      this.bind();
    }
    bind(){
      this.bindDrop = e=>{
        e.preventDefault();
        if(this.disabled){ this.showError('游客暂未开放上传,请先登录后再试'); return; }
        this.handleFiles(e.dataTransfer.files);
      };
      if(this.dropEl){
        ['dragenter','dragover'].forEach(ev=>this.dropEl.addEventListener(ev, e=>{e.preventDefault(); if(!this.disabled) this.dropEl.classList.add('dragover');}));
        ['dragleave','drop'].forEach(ev=>this.dropEl.addEventListener(ev, e=>{e.preventDefault();this.dropEl.classList.remove('dragover');}));
        this.dropEl.addEventListener('drop', this.bindDrop);
        this.dropEl.addEventListener('click', ()=>{
          if(this.disabled){ this.showError('游客暂未开放上传,请先登录后再试'); return; }
          this.fileInput && this.fileInput.click();
        });
      }
      if(this.fileInput){
        this.fileInput.addEventListener('change', e=>this.handleFiles(e.target.files));
      }
      // 全局粘贴上传(Ctrl+V / Cmd+V)
      document.addEventListener('paste', e=>{
        const items = e.clipboardData && e.clipboardData.items;
        if(!items) return;
        for(const it of items){
          if(it.kind === 'file'){
            const f = it.getAsFile();
            if(f){ e.preventDefault(); this.uploadFile(f); break; }
          }
        }
      });
    }
    handleFiles(fileList){
      const files = Array.from(fileList || []);
      files.forEach(f=>this.uploadFile(f));
    }
    uploadFile(file){
      // 前端类型预检(服务端会再次严格校验)
      if(!/^image\/(jpeg|png|gif|webp)$/.test(file.type) && !/\.(jpg|jpeg|png|gif|webp)$/i.test(file.name)){
        this.showError('仅支持 JPG / PNG / GIF / WEBP 图片'); return;
      }
      if(this.disabled){
        this.showError('游客暂未开放上传,请先登录后再试'); return;
      }
      const fd = new FormData();
      fd.append('file', file);
      for(const k in this.extraData) fd.append(k, this.extraData[k]);

      const xhr = new XMLHttpRequest();
      xhr.open('POST', this.apiUrl, true);
      // 显示进度条
      this.progressBar && this.progressBar.classList.add('on');
      xhr.upload.onprogress = e=>{
        if(e.lengthComputable && this.progressEl){
          const pct = Math.round(e.loaded / e.total * 100);
          this.progressEl.style.width = pct + '%';
        }
      };
      xhr.onload = ()=>{
        this.progressBar && this.progressBar.classList.remove('on');
        this.progressEl && (this.progressEl.style.width = '0');
        let res;
        try{ res = JSON.parse(xhr.responseText); }catch(e){ res = {ok:false,msg:'服务器返回异常'}; }
        if(xhr.status === 200 && res.ok){
          this.renderResult(res);
          this.onSuccess(res);
        }else{
          this.showError(res.msg || '上传失败 (´-ω-`)');
        }
      };
      xhr.onerror = ()=>{ this.progressBar && this.progressBar.classList.remove('on'); this.showError('网络错误,请稍后重试'); };
      xhr.send(fd);
    }
    // 将错误信息常驻显示在结果区(而非仅一闪而过的 toast)
    showError(msg){
      window.toast(msg, false); // 保留 toast 即时反馈
      if(this.resultEl){
        this.resultEl.innerHTML = `<div class="alert alert-err" style="margin:10px 0">⚠️ ${msg.replace(/</g,'&lt;').replace(/>/g,'&gt;')}</div>`;
        this.resultEl.classList.add('on');
      }
    }
    renderResult(res){
      if(!this.resultEl) return;
      const r = res.data;
      const rows = [
        {label:'原图链接 (URL)', value:r.url},
        {label:'Markdown', value:r.markdown},
        {label:'HTML', value:r.html},
        {label:'BBCode', value:r.bbcode},
      ];
      let html = `<div class="card result on" style="display:block">
        <img src="${r.url}" alt="preview" onload="this.classList.add('loaded')">
        <div class="img-meta text-muted" style="font-size:.88rem;margin:8px 0">${r.filename} · ${r.size_human} · ${r.width}×${r.height}</div>`;
      rows.forEach(row=>{
        const esc = row.value.replace(/&/g,'&amp;').replace(/"/g,'&quot;');
        html += `<div class="link-row">
          <input type="text" value="${esc}" readonly>
          <button type="button" class="btn btn-sm btn-primary copy-btn" data-copy="${esc}">复制 ${row.label}</button>
        </div>`;
      });
      html += `</div>`;
      this.resultEl.innerHTML = html;
      this.resultEl.classList.add('on');
      window.toast('上传成功 ฅ^•ﻌ•^ฅ');
    }
  }

  window.Uploader = Uploader;

  /* ---------- 自动初始化首页上传器 ---------- */
  document.addEventListener('DOMContentLoaded', ()=>{
    const drop = document.getElementById('dropZone');
    if(drop){
      window._uploader = new Uploader({
        dropEl: drop,
        fileInput: document.getElementById('fileInput'),
        progressEl: document.querySelector('#progress > span'),
        progressBar: document.getElementById('progress'),
        resultEl: document.getElementById('result'),
        disabled: drop.classList.contains('disabled'), // 游客未开放时禁用上传
        extraData: {csrf: (document.querySelector('[name=csrf]')||{}).value || ''},
      });
    }
  });

})();

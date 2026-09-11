
function validateRegisterMatch(pId, cpId){
  const p = document.getElementById(pId);
  const cp = document.getElementById(cpId);
  if (!p || !cp) return true;
  if (p.value !== cp.value){
    alert("Passwords do not match");
    cp.focus();
    return false;
  }
  return true;
}

function onlyNumbers(evt){
  const c = evt.which || evt.keyCode;
  if (c >= 48 && c <= 57) return true;
  evt.preventDefault();
  return false;
}

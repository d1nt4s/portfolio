export class Pomodoro
{
  timer;
  seconds = 0;
  minutes;
  interval;

  stages_names = new Map ([['session', 'Session'],['short_break', 'Short break'],['long_break', 'Long break']]);
  // next => [ session ; short_break ; long_break ; break_interval ]
  next = 'session';

  // stages => [ session ; short_break ; long_break ; long_break_interval ]
  stages;

  // completed_stages => [ [0] of sessions; [1] of short_breaks; [2] of long_breaks ]
  completed_stages = [0, 0, 0];

  startStageDate;
  stopStageDate;

  constructor (stages)
  {
    this.stages = stages;
    this.selectStage();
  }

  selectStage()
  {

    switch (this.next) {
      case 'session':
        this.setStage('session');
        break;
      case 'short_break':
        this.setStage('short_break');
        break;
      case 'long_break':
        this.setStage('long_break');
        break;
    }

  }

  completeStage()
  {
    this.pauseStage();

    switch (this.next) {
      case 'session':

        this.completed_stages[0]++;

        if (this.completed_stages[0] % this.stages.get('long_break_interval') == 0)
          this.next = 'long_break';
        else
          this.next = 'short_break';

        break;
      case 'short_break':
        this.next = 'session';
        this.completed_stages[1]++;
        break;
      case 'long_break':
        this.next = 'session';
        this.completed_stages[2]++;
        break;
    }

    this.showCompletedSessions();
  }

  Stage()
  {
    if (this.minutes == 0 && this.seconds == 0) {

      // alert("GO EAT");

      this.completeStage();
      this.selectStage();

      return;
    }
    if (this.seconds == 0) {
      this.minutes--;
      // this.seconds = 60;
      this.seconds = 2;
    }
    this.seconds--;

    this.timer.textContent = `${this.minutes.toString()}:${this.seconds.toString().padStart(2, '0')}`;
  }

  showCompletedSessions()
  {
    document.getElementById('compl_sess').innerHTML = this.completed_stages[0];
  }

  setStage(stage)
  {
    this.minutes = this.stages.get(stage);
    this.timer = document.getElementById('timer');
    document.getElementById('stage').innerHTML = this.stages_names.get(stage);
    this.timer.innerHTML = this.minutes + ':00';
  }

  startStage()
  {
    this.startStageDate = new Date().toString();
    this.startStageDate = this.dateTreatment(this.startStageDate);

    document.getElementById('start_pomodoro').style.display = "none";
    document.getElementById('pause_pomodoro').style.display = "block";
  }

  pauseStage()
  {
    clearInterval(this.interval);
    this.stopStageDate = new Date().toString();
    this.stopStageDate = this.dateTreatment(this.stopStageDate); 
    
    this.sendToTimeNettoData();

    document.getElementById('start_pomodoro').style.display = "block";
    document.getElementById('pause_pomodoro').style.display = "none";
  }

  dateTreatment(str)
  {
    let index = str.indexOf("GMT");
    str = str.slice(0, index-1);
    return str;
  }

  sendToTimeNettoData()
  {
    let pomodoro_stage_data = [this.startStageDate, this.stopStageDate, this.stages_names.get(this.next)];

    pomodoro_stage_data = JSON.stringify(pomodoro_stage_data);

    $.ajax({
      type:'POST',
      url:'timenetto',
      data:{
        pomodoro_stage_data
      },
      success:function(data){
        console.log(data)
      }
    });

  }
}
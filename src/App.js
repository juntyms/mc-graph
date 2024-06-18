import { useEffect, useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';
import { SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer } from 'recharts';
import './App.css';

const App = () => {
  const [data, setData] = useState([]);
  const [days, setDays] = useState(7);

 
  useEffect(() => {
    apiFetch({ path: `/mc_graph/v1/data?days=${days}` })
      .then(response => {
            return JSON.parse(JSON.stringify(response));
      })
      .then(data => {
        //console.log(data);
        setData(data);
      })
      .catch(error => console.error('Error fetching data:', error));
  }, [days]);


  const daysChange = (value) => {
    setDays(Number(value));
  };

return (
  <div>
    <div className="graph-widget">
      <label htmlFor="days">{__('Graph Widget', 'mc-graph-option')}</label>
      <SelectControl
          id="days"
          value={days}
          options={[
            { label: mcGraphData.i18n.last_7_days, value: 7 },
            { label: mcGraphData.i18n.last_15_days, value: 15 },
            { label: mcGraphData.i18n.last_1_month, value: 30 },
          ]}
          onChange={daysChange}
        />
    </div>
    <div style={{ width: '400', height: '300' }}>
      <ResponsiveContainer height="100%" aspect={1}>
        <LineChart
          width={500}
          height={300}
          data={data}
          margin={{
            top: 5,
            right: 30,
            left: 20,
            bottom: 5,
          }}
        >
          <CartesianGrid strokeDasharray="3 3" />
          <XAxis dataKey="date" />
          <YAxis domain={[0,20]} />
          <Tooltip />
          <Legend />
          <Line type="monotone" dataKey="value" stroke="#8884d8" activeDot={{ r: 8 }} />
        </LineChart>
      </ResponsiveContainer>
    </div>
  </div>
  );
};
export default App;
